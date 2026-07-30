from __future__ import annotations

import importlib.util
import json
import tempfile
import unittest
from pathlib import Path


MODULE_PATH = Path(__file__).parents[1] / "scripts" / "i18n_guard.py"
SPEC = importlib.util.spec_from_file_location("i18n_guard", MODULE_PATH)
if SPEC is None or SPEC.loader is None:
    raise RuntimeError(f"Cannot load {MODULE_PATH}")
GUARD = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(GUARD)


class I18nGuardTest(unittest.TestCase):
    def setUp(self) -> None:
        self.temporary_directory = tempfile.TemporaryDirectory()
        self.workspace = Path(self.temporary_directory.name)
        self.root = self.workspace / "i18n"
        self.source_root = self.workspace / "docs"
        self.source_path = self.source_root / "index.md"
        self.localized_path = (
            self.root
            / "be"
            / "docusaurus-plugin-content-docs"
            / "current"
            / "index.md"
        )
        self.document_path = self.root / "be" / "nested" / "code.json"
        self.document_path.parent.mkdir(parents=True)
        self.document = {
            "theme.example": {
                "message": "Open {name} for {name}",
                "description": "The `open` label for `open` at https://example.com",
                "fixed": "unchanged",
            },
            "theme.plural": {
                "message": "{count} page|{count} pages",
                "description": "Pluralized page count",
            },
        }
        self.write_document(self.document)
        self.source_markdown = """---
sidebar_position: 1
slug: /
title: Overview
description: Learn about `Widget`.
---

[Next](next.md)

# Overview

Create a `Widget` for the application. Read the `Overview` section first.

```php
Widget::make();
```
"""
        self.localized_markdown = """---
sidebar_position: 1
slug: /
title: Агляд
description: Даведайцеся пра `Widget`.
---

[Далей](next.md)

# Агляд

Стварыце `Widget` для праграмы. Спачатку прачытайце раздзел `Агляд`.

```php
Widget::make();
```
"""
        self.source_path.parent.mkdir(parents=True)
        self.source_path.write_text(self.source_markdown, encoding="utf-8")
        self.write_localized_markdown(self.localized_markdown)
        self.snapshot_path = self.workspace / "snapshot.json"
        GUARD.create_snapshot(
            self.root,
            ["be"],
            self.snapshot_path,
            self.source_root,
        )

    def tearDown(self) -> None:
        self.temporary_directory.cleanup()

    def write_document(self, document: dict[str, object]) -> None:
        self.document_path.write_text(
            json.dumps(document, ensure_ascii=False, indent=4) + "\n",
            encoding="utf-8",
        )

    def write_localized_markdown(self, value: str) -> None:
        self.localized_path.parent.mkdir(parents=True, exist_ok=True)
        self.localized_path.write_text(value, encoding="utf-8")

    def validate(self) -> list[str]:
        errors, _ = GUARD.validate_snapshot(self.root, self.snapshot_path)
        return errors

    def test_accepts_translated_fields_and_locale_plural_forms(self) -> None:
        self.document["theme.example"]["message"] = "Адкрыць {name} для {name}"
        self.document["theme.example"]["description"] = (
            "Подпіс `open` для `open` на https://example.com"
        )
        self.document["theme.plural"]["message"] = (
            "{count} старонка|{count} старонкі|{count} старонак"
        )
        self.document["theme.plural"]["description"] = "Формы колькасці старонак"
        self.write_document(self.document)

        self.assertEqual([], self.validate())

    def test_rejects_translation_key_changes(self) -> None:
        entry = self.document.pop("theme.example")
        self.document["theme.renamed"] = entry
        self.write_document(self.document)

        self.assertTrue(any("translation key" in error for error in self.validate()))

    def test_rejects_entry_structure_changes(self) -> None:
        self.document["theme.example"]["extra"] = "value"
        self.write_document(self.document)

        self.assertTrue(any("entry fields" in error for error in self.validate()))

    def test_rejects_protected_token_changes(self) -> None:
        self.document["theme.example"]["message"] = "Адкрыць {title}"
        self.write_document(self.document)

        self.assertTrue(any("protected tokens" in error for error in self.validate()))

    def test_rejects_non_translatable_value_changes(self) -> None:
        self.document["theme.example"]["fixed"] = "changed"
        self.write_document(self.document)

        self.assertTrue(any("non-translatable value" in error for error in self.validate()))

    def test_rejects_file_renames(self) -> None:
        self.document_path.rename(self.document_path.with_name("renamed.json"))

        self.assertTrue(any("File paths changed" in error for error in self.validate()))

    def test_accepts_missing_markdown_added_after_snapshot(self) -> None:
        self.localized_path.unlink()
        self.localized_path.parent.rmdir()
        self.localized_path.parent.parent.rmdir()
        snapshot_path = self.workspace / "missing-markdown-snapshot.json"
        GUARD.create_snapshot(
            self.root,
            ["be"],
            snapshot_path,
            self.source_root,
        )
        self.write_localized_markdown(self.localized_markdown)

        errors, _ = GUARD.validate_snapshot(self.root, snapshot_path)

        self.assertEqual([], errors)

    def test_rejects_unexpected_localized_markdown(self) -> None:
        unexpected = self.localized_path.with_name("unexpected.md")
        unexpected.write_text(self.localized_markdown, encoding="utf-8")

        self.assertTrue(
            any("Unexpected localized Markdown files" in error for error in self.validate())
        )

    def test_rejects_missing_localized_markdown(self) -> None:
        self.localized_path.unlink()

        self.assertTrue(
            any("Localized Markdown files missing" in error for error in self.validate())
        )

    def test_rejects_markdown_code_changes(self) -> None:
        self.write_localized_markdown(
            self.localized_markdown.replace("Widget::make();", "Widget::create();")
        )

        self.assertTrue(any("fenced code blocks" in error for error in self.validate()))

    def test_rejects_markdown_link_target_changes(self) -> None:
        self.write_localized_markdown(
            self.localized_markdown.replace("next.md", "other.md")
        )

        self.assertTrue(any("link targets" in error for error in self.validate()))

    def test_rejects_markdown_front_matter_changes(self) -> None:
        self.write_localized_markdown(
            self.localized_markdown.replace("sidebar_position: 1", "sidebar_position: 2")
        )

        self.assertTrue(any("front matter structure" in error for error in self.validate()))

    def test_rejects_markdown_identical_to_source(self) -> None:
        self.write_localized_markdown(self.source_markdown)

        self.assertTrue(any("identical to the source" in error for error in self.validate()))


if __name__ == "__main__":
    unittest.main()
