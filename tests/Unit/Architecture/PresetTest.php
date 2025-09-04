<?php

declare(strict_types=1);

arch()->preset()->php()->ignoring('Workbench\App');

arch()->preset()->laravel();
arch()->preset()->security()->ignoring(['assert']);
