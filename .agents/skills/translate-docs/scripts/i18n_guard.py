from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import sys
import tempfile
from pathlib import Path, PurePosixPath
from typing import Any


TRANSLATABLE_FIELDS = {"message", "description"}
TRANSLATABLE_FRONT_MATTER_FIELDS = {
    "description",
    "pagination_label",
    "sidebar_label",
    "title",
}
CONTENT_SUBPATH = PurePosixPath("docusaurus-plugin-content-docs/current")
MARKDOWN_SUFFIXES = {".md", ".mdx"}
TOKEN_PATTERNS = (
    re.compile(r"\{[^{}\r\n]+\}"),
    re.compile(r"https?://[^\s<>()\"']+"),
    re.compile(r"`[^`\r\n]+`"),
    re.compile(r"</?[A-Za-z][^>\r\n]*>"),
    re.compile(r"(?<!%)%(?:\d+\$)?[-+#0 ]*\d*(?:\.\d+)?[A-Za-z]"),
    re.compile(r":::[A-Za-z][A-Za-z0-9_-]*"),
)
FENCED_BLOCK_PATTERN = re.compile(
    r"^(?P<fence>`{3,}|~{3,})[^\n]*\n.*?^(?P=fence)[ \t]*$",
    re.MULTILINE | re.DOTALL,
)
MARKDOWN_LINK_PATTERN = re.compile(r"!?\[[^\]\r\n]*\]\(([^)\r\n]+)\)")
REFERENCE_LINK_PATTERN = re.compile(r"^\s*\[([^\]]+)\]:\s*(\S+)", re.MULTILINE)


def normalized_path(path: Path) -> str:
    return os.path.normcase(os.path.normpath(str(path.resolve())))


def relative_path(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def file_hash(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()


def normalized_text(path: Path) -> str:
    return path.read_text(encoding="utf-8-sig").replace("\r\n", "\n").replace("\r", "\n")


def is_localized_markdown(relative: str, locales: list[str]) -> bool:
    path = PurePosixPath(relative)
    parts = path.parts
    prefix = CONTENT_SUBPATH.parts

    return (
        path.suffix.lower() in MARKDOWN_SUFFIXES
        and len(parts) > len(prefix) + 1
        and parts[0] in locales
        and parts[1 : len(prefix) + 1] == prefix
    )


def load_translation_document(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8-sig") as stream:
        document = json.load(stream)

    if not isinstance(document, dict):
        raise ValueError(f"{path}: JSON root must be an object")

    for key, entry in document.items():
        if not isinstance(entry, dict):
            raise ValueError(f"{path}: entry {key!r} must be an object")
        if not isinstance(entry.get("message"), str):
            raise ValueError(f"{path}: entry {key!r} must contain a string message")
        if "description" in entry and not isinstance(entry["description"], str):
            raise ValueError(f"{path}: entry {key!r} description must be a string")

    return document


def resolve_locales(root: Path, requested: list[str] | None) -> list[str]:
    if not root.is_dir():
        raise ValueError(f"Localization root does not exist: {root}")

    available = sorted(path.name for path in root.iterdir() if path.is_dir())
    if not available:
        raise ValueError(f"No locale directories found below {root}")

    if not requested:
        return available

    locales = list(dict.fromkeys(requested))
    invalid = [locale for locale in locales if locale not in available]
    if invalid:
        raise ValueError(f"Unknown locale directories: {', '.join(invalid)}")

    return locales


def collect_state(root: Path, locales: list[str]) -> dict[str, Any]:
    directories: list[str] = []
    files: list[str] = []
    documents: dict[str, dict[str, Any]] = {}
    markdown_files: list[str] = []
    other_hashes: dict[str, str] = {}

    for locale in locales:
        locale_root = root / locale
        if not locale_root.is_dir():
            raise ValueError(f"Locale directory does not exist: {locale_root}")

        directories.append(relative_path(locale_root, root))

        for path in sorted(locale_root.rglob("*"), key=lambda item: item.as_posix()):
            relative = relative_path(path, root)
            if path.is_dir():
                directories.append(relative)
                continue
            if not path.is_file():
                raise ValueError(f"Unsupported filesystem entry: {path}")

            files.append(relative)
            if path.suffix.lower() == ".json":
                documents[relative] = load_translation_document(path)
            elif is_localized_markdown(relative, locales):
                markdown_files.append(relative)
            else:
                other_hashes[relative] = file_hash(path)

    return {
        "directories": directories,
        "files": files,
        "documents": documents,
        "markdown_files": markdown_files,
        "other_hashes": other_hashes,
    }


def write_snapshot(payload: dict[str, Any], output: Path | None) -> Path:
    if output is None:
        descriptor, name = tempfile.mkstemp(prefix="translate-docs-", suffix=".json")
        with os.fdopen(descriptor, "w", encoding="utf-8", newline="\n") as stream:
            json.dump(payload, stream, ensure_ascii=False, indent=2)
            stream.write("\n")
        return Path(name).resolve()

    output = output.resolve()
    if output.exists():
        raise ValueError(f"Snapshot already exists: {output}")
    if not output.parent.is_dir():
        raise ValueError(f"Snapshot parent directory does not exist: {output.parent}")

    with output.open("x", encoding="utf-8", newline="\n") as stream:
        json.dump(payload, stream, ensure_ascii=False, indent=2)
        stream.write("\n")

    return output


def create_snapshot(
    root: Path,
    requested_locales: list[str] | None = None,
    output: Path | None = None,
    source_root: Path | None = None,
) -> tuple[Path, dict[str, Any]]:
    root = root.resolve()
    source_root = (source_root or Path("docs/docs")).resolve()
    locales = resolve_locales(root, requested_locales)
    state = collect_state(root, locales)
    source_documents = collect_source_documents(source_root)
    payload = {
        "version": 2,
        "root": str(root),
        "source_root": str(source_root),
        "locales": locales,
        "source_documents": source_documents,
        "expected_markdown": expected_markdown_paths(locales, source_documents),
        **state,
    }
    return write_snapshot(payload, output), payload


def load_snapshot(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as stream:
        payload = json.load(stream)

    if not isinstance(payload, dict):
        raise ValueError(f"Snapshot root must be an object: {path}")
    if payload.get("version") != 2:
        raise ValueError(f"Unsupported snapshot version: {payload.get('version')!r}")
    if not isinstance(payload.get("root"), str):
        raise ValueError("Snapshot root path is missing")
    if not isinstance(payload.get("source_root"), str):
        raise ValueError("Snapshot source root path is missing")
    if not isinstance(payload.get("locales"), list) or not all(
        isinstance(locale, str) for locale in payload["locales"]
    ):
        raise ValueError("Snapshot locales are invalid")

    for field in ("directories", "files", "markdown_files"):
        if not isinstance(payload.get(field), list) or not all(
            isinstance(value, str) for value in payload[field]
        ):
            raise ValueError(f"Snapshot field {field!r} is invalid")

    for field in ("documents", "other_hashes", "source_documents", "expected_markdown"):
        if not isinstance(payload.get(field), dict):
            raise ValueError(f"Snapshot field {field!r} is invalid")

    return payload


def protected_tokens(value: str) -> list[list[str]]:
    return [sorted(set(pattern.findall(value))) for pattern in TOKEN_PATTERNS]


def split_front_matter(value: str) -> tuple[list[str], str]:
    if not value.startswith("---\n"):
        return [], value

    end = value.find("\n---\n", 4)
    if end < 0:
        raise ValueError("Markdown front matter is not closed")

    return value[4:end].split("\n"), value[end + 5 :]


def front_matter_signature(lines: list[str]) -> list[str]:
    signature: list[str] = []
    field_pattern = re.compile(r"^(\s*)([A-Za-z0-9_-]+):\s*(.*)$")

    for line in lines:
        match = field_pattern.match(line)
        if match and match.group(2) in TRANSLATABLE_FRONT_MATTER_FIELDS:
            signature.append(f"{match.group(1)}{match.group(2)}: <translated>")
        else:
            signature.append(line)

    return signature


def front_matter_tokens(lines: list[str]) -> dict[str, list[list[str]]]:
    tokens: dict[str, list[list[str]]] = {}
    field_pattern = re.compile(r"^\s*([A-Za-z0-9_-]+):\s*(.*)$")

    for line in lines:
        match = field_pattern.match(line)
        if match and match.group(1) in TRANSLATABLE_FRONT_MATTER_FIELDS:
            tokens[match.group(1)] = protected_tokens(match.group(2))

    return tokens


def markdown_link_targets(value: str) -> list[str]:
    targets: list[str] = []

    for match in MARKDOWN_LINK_PATTERN.finditer(value):
        target = match.group(1).strip()
        if target.startswith("<") and ">" in target:
            targets.append(target[: target.index(">") + 1])
        else:
            targets.append(target.split(maxsplit=1)[0])

    return targets


def markdown_signature(value: str) -> dict[str, Any]:
    front_matter, body = split_front_matter(value)
    fenced_blocks = [match.group(0) for match in FENCED_BLOCK_PATTERN.finditer(body)]
    prose = FENCED_BLOCK_PATTERN.sub("", body)
    headings = list(
        re.finditer(r"^(#{1,6})[ \t]+(.+?)[ \t]*#*[ \t]*$", prose, re.MULTILINE)
    )
    tokens = protected_tokens(prose)
    heading_labels = {match.group(2) for match in headings}
    tokens[2] = [token for token in tokens[2] if token[1:-1] not in heading_labels]

    return {
        "front_matter": front_matter_signature(front_matter),
        "front_matter_tokens": front_matter_tokens(front_matter),
        "fenced_blocks": fenced_blocks,
        "heading_levels": [len(match.group(1)) for match in headings],
        "link_targets": markdown_link_targets(prose),
        "reference_links": [
            list(match.groups()) for match in REFERENCE_LINK_PATTERN.finditer(prose)
        ],
        "mdx_statements": re.findall(r"^(?:import|export)\s+.*$", prose, re.MULTILINE),
        "table_shapes": [
            len(re.findall(r"(?<!\\)\|", line))
            for line in prose.splitlines()
            if line.lstrip().startswith("|")
        ],
        "list_shapes": [
            "ordered" if match.group(1)[0].isdigit() else "unordered"
            for match in re.finditer(r"^\s*([-+*]|\d+\.)[ \t]+", prose, re.MULTILINE)
        ],
        "protected_tokens": tokens,
    }


def collect_source_documents(root: Path) -> dict[str, dict[str, Any]]:
    if not root.is_dir():
        raise ValueError(f"Documentation source root does not exist: {root}")

    documents: dict[str, dict[str, Any]] = {}
    for path in sorted(root.rglob("*"), key=lambda item: item.as_posix()):
        if path.is_file() and path.suffix.lower() in MARKDOWN_SUFFIXES:
            relative = relative_path(path, root)
            documents[relative] = {
                "hash": file_hash(path),
                "signature": markdown_signature(normalized_text(path)),
            }

    if not documents:
        raise ValueError(f"No Markdown documentation found below {root}")

    return documents


def expected_markdown_paths(
    locales: list[str],
    source_documents: dict[str, dict[str, Any]],
) -> dict[str, str]:
    return {
        (PurePosixPath(locale) / CONTENT_SUBPATH / source).as_posix(): source
        for locale in locales
        for source in source_documents
    }


def compare_markdown(
    relative: str,
    expected: dict[str, Any],
    actual: dict[str, Any],
    errors: list[str],
) -> None:
    labels = {
        "front_matter": "front matter structure",
        "front_matter_tokens": "front matter protected tokens",
        "fenced_blocks": "fenced code blocks",
        "heading_levels": "heading hierarchy",
        "link_targets": "link targets",
        "reference_links": "reference link targets",
        "mdx_statements": "MDX import/export statements",
        "table_shapes": "table structure",
        "list_shapes": "list structure",
        "protected_tokens": "protected tokens",
    }

    for field, label in labels.items():
        if expected.get(field) != actual.get(field):
            errors.append(f"{relative}: {label} changed")


def compare_document(
    relative: str,
    before: dict[str, Any],
    after: dict[str, Any],
    errors: list[str],
) -> None:
    before_keys = list(before.keys())
    after_keys = list(after.keys())
    if before_keys != after_keys:
        errors.append(f"{relative}: translation key set or order changed")

    for key in before.keys() & after.keys():
        before_entry = before[key]
        after_entry = after[key]

        if not isinstance(before_entry, dict) or not isinstance(after_entry, dict):
            errors.append(f"{relative}:{key}: entry type changed")
            continue

        before_fields = list(before_entry.keys())
        after_fields = list(after_entry.keys())
        if before_fields != after_fields:
            errors.append(f"{relative}:{key}: entry fields or field order changed")

        for field in before_entry.keys() & after_entry.keys():
            before_value = before_entry[field]
            after_value = after_entry[field]

            if field in TRANSLATABLE_FIELDS:
                if not isinstance(before_value, str) or not isinstance(after_value, str):
                    errors.append(f"{relative}:{key}.{field}: value type changed")
                    continue
                if protected_tokens(before_value) != protected_tokens(after_value):
                    errors.append(f"{relative}:{key}.{field}: protected tokens changed")
                continue

            if before_value != after_value:
                errors.append(f"{relative}:{key}.{field}: non-translatable value changed")


def validate_snapshot(root: Path, snapshot_path: Path) -> tuple[list[str], dict[str, Any]]:
    root = root.resolve()
    snapshot = load_snapshot(snapshot_path)
    errors: list[str] = []

    if normalized_path(root) != os.path.normcase(os.path.normpath(snapshot["root"])):
        errors.append(f"Localization root differs from snapshot: {root}")

    source_root = Path(snapshot["source_root"])
    current_source = collect_source_documents(source_root)
    expected_source = snapshot["source_documents"]
    if list(expected_source) != list(current_source):
        errors.append("Documentation source file paths changed after the snapshot")

    for relative, document in expected_source.items():
        if (
            relative in current_source
            and document.get("hash") != current_source[relative].get("hash")
        ):
            errors.append(f"{relative}: documentation source changed after the snapshot")

    current = collect_state(root, snapshot["locales"])
    expected_markdown = snapshot["expected_markdown"]
    expected_markdown_set = set(expected_markdown)

    before_files = set(snapshot["files"])
    after_files = set(current["files"])
    removed_files = sorted(before_files - after_files)
    added_files = sorted(after_files - before_files)
    invalid_added_files = [path for path in added_files if path not in expected_markdown_set]
    if removed_files:
        errors.append(f"File paths changed: removed={removed_files}")
    if invalid_added_files:
        errors.append(f"File paths changed: unexpected additions={invalid_added_files}")

    allowed_directories: set[str] = set()
    for relative in expected_markdown:
        parent = PurePosixPath(relative).parent
        while len(parent.parts) > 1:
            allowed_directories.add(parent.as_posix())
            parent = parent.parent

    before_directories = set(snapshot["directories"])
    after_directories = set(current["directories"])
    removed_directories = sorted(before_directories - after_directories)
    invalid_added_directories = sorted(
        path
        for path in after_directories - before_directories
        if path not in allowed_directories
    )
    if removed_directories:
        errors.append(f"Directory paths changed: removed={removed_directories}")
    if invalid_added_directories:
        errors.append(
            f"Directory paths changed: unexpected additions={invalid_added_directories}"
        )

    for relative, expected_hash in snapshot["other_hashes"].items():
        if (
            relative in current["other_hashes"]
            and current["other_hashes"][relative] != expected_hash
        ):
            errors.append(f"{relative}: non-JSON file content changed")

    before_documents = snapshot["documents"]
    after_documents = current["documents"]
    for relative in before_documents.keys() & after_documents.keys():
        compare_document(
            relative,
            before_documents[relative],
            after_documents[relative],
            errors,
        )

    current_markdown = set(current["markdown_files"])
    missing_markdown = sorted(expected_markdown_set - current_markdown)
    unexpected_markdown = sorted(current_markdown - expected_markdown_set)
    if missing_markdown:
        errors.append(f"Localized Markdown files missing: {missing_markdown}")
    if unexpected_markdown:
        errors.append(f"Unexpected localized Markdown files: {unexpected_markdown}")

    for relative, source_relative in expected_markdown.items():
        if relative not in current_markdown or source_relative not in expected_source:
            continue

        target_path = root / PurePosixPath(relative)
        source_document = expected_source[source_relative]
        target_text = normalized_text(target_path)
        compare_markdown(
            relative,
            source_document["signature"],
            markdown_signature(target_text),
            errors,
        )
        source_path = source_root / PurePosixPath(source_relative)
        if target_text == normalized_text(source_path):
            errors.append(f"{relative}: localized page is identical to the source page")

    return errors, current


def entry_count(documents: dict[str, dict[str, Any]]) -> int:
    return sum(len(document) for document in documents.values())


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(prog="i18n_guard.py")
    commands = parser.add_subparsers(dest="command", required=True)

    snapshot = commands.add_parser("snapshot")
    snapshot.add_argument("--root", default="docs/i18n")
    snapshot.add_argument("--source-root", default="docs/docs")
    snapshot.add_argument("--locale", action="append", dest="locales")
    snapshot.add_argument("--snapshot", dest="snapshot_path")

    validate = commands.add_parser("validate")
    validate.add_argument("--root", default="docs/i18n")
    validate.add_argument("--snapshot", required=True, dest="snapshot_path")
    validate.add_argument("--delete-snapshot", action="store_true")

    return parser


def main() -> int:
    args = build_parser().parse_args()

    try:
        if args.command == "snapshot":
            snapshot_path = Path(args.snapshot_path) if args.snapshot_path else None
            path, payload = create_snapshot(
                Path(args.root),
                args.locales,
                snapshot_path,
                Path(args.source_root),
            )
            print(f"Snapshot: {path}")
            print(f"Locales: {', '.join(payload['locales'])}")
            print(f"JSON files: {len(payload['documents'])}")
            print(f"Translation entries: {entry_count(payload['documents'])}")
            print(f"Source Markdown pages: {len(payload['source_documents'])}")
            return 0

        snapshot_path = Path(args.snapshot_path).resolve()
        errors, current = validate_snapshot(Path(args.root), snapshot_path)
        if errors:
            print("Validation failed:", file=sys.stderr)
            for error in errors:
                print(f"- {error}", file=sys.stderr)
            print(f"Snapshot retained: {snapshot_path}", file=sys.stderr)
            return 1

        print(f"Validation passed: {len(current['documents'])} JSON files")
        print(f"Translation entries: {entry_count(current['documents'])}")
        print(f"Localized Markdown pages: {len(current['markdown_files'])}")
        if args.delete_snapshot:
            snapshot_path.unlink()
            print(f"Snapshot deleted: {snapshot_path}")
        return 0
    except (OSError, ValueError, json.JSONDecodeError) as error:
        print(f"ERROR: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
