<?php

/**
 * JBZoo Toolbox - Utils.
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @see        https://github.com/JBZoo/Utils
 */

declare(strict_types=1);

namespace JBZoo\Utils;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ShortClassName)
 */
final class FS
{
    public const TYPE_SOCKET    = 0xC000;
    public const TYPE_SYMLINK   = 0xA000;
    public const TYPE_REGULAR   = 0x8000;
    public const TYPE_BLOCK     = 0x6000;
    public const TYPE_DIR       = 0x4000;
    public const TYPE_CHARACTER = 0x2000;
    public const TYPE_FIFO      = 0x1000;

    public const PERM_OWNER_READ        = 0x0100;
    public const PERM_OWNER_WRITE       = 0x0080;
    public const PERM_OWNER_EXEC        = 0x0040;
    public const PERM_OWNER_EXEC_STICKY = 0x0800;

    public const PERM_GROUP_READ        = 0x0020;
    public const PERM_GROUP_WRITE       = 0x0010;
    public const PERM_GROUP_EXEC        = 0x0008;
    public const PERM_GROUP_EXEC_STICKY = 0x0400;

    public const PERM_ALL_READ        = 0x0004;
    public const PERM_ALL_WRITE       = 0x0002;
    public const PERM_ALL_EXEC        = 0x0001;
    public const PERM_ALL_EXEC_STICKY = 0x0200;

    /**
     * Returns the file permissions as a nice string, like -rw-r--r-- or false if the file is not found.
     *
     * @param string   $file  The name of the file to get permissions form
     * @param null|int $perms numerical value of permissions to display as text
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function perms(string $file, ?int $perms = null): string
    {
        if ($perms === null) {
            if (!\file_exists($file)) {
                return '';
            }

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $perms = \fileperms($file);
        }

        /** @codeCoverageIgnoreStart */
        $info = 'u'; // undefined
        if (($perms & self::TYPE_SOCKET) === self::TYPE_SOCKET) {
            $info = 's';
        } elseif (($perms & self::TYPE_SYMLINK) === self::TYPE_SYMLINK) {
            $info = 'l';
        } elseif (($perms & self::TYPE_REGULAR) === self::TYPE_REGULAR) {
            $info = '-';
        } elseif (($perms & self::TYPE_BLOCK) === self::TYPE_BLOCK) {
            $info = 'b';
        } elseif (($perms & self::TYPE_DIR) === self::TYPE_DIR) {
            $info = 'd';
        } elseif (($perms & self::TYPE_CHARACTER) === self::TYPE_CHARACTER) {
            $info = 'c';
        } elseif (($perms & self::TYPE_FIFO) === self::TYPE_FIFO) {
            $info = 'p';
        }
        // @codeCoverageIgnoreEnd

        // Owner
        $info .= (($perms & self::PERM_OWNER_READ) > 0 ? 'r' : '-');
        $info .= (($perms & self::PERM_OWNER_WRITE) > 0 ? 'w' : '-');

        /** @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & self::PERM_OWNER_EXEC) > 0
            ? (($perms & self::PERM_OWNER_EXEC_STICKY) > 0 ? 's' : 'x')
            : (($perms & self::PERM_OWNER_EXEC_STICKY) > 0 ? 'S' : '-'));

        // Group
        $info .= (($perms & self::PERM_GROUP_READ) > 0 ? 'r' : '-');
        $info .= (($perms & self::PERM_GROUP_WRITE) > 0 ? 'w' : '-');

        /** @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & self::PERM_GROUP_EXEC) > 0
            ? (($perms & self::PERM_GROUP_EXEC_STICKY) > 0 ? 's' : 'x')
            : (($perms & self::PERM_GROUP_EXEC_STICKY) > 0 ? 'S' : '-'));

        // All
        $info .= (($perms & self::PERM_ALL_READ) > 0 ? 'r' : '-');
        $info .= (($perms & self::PERM_ALL_WRITE) > 0 ? 'w' : '-');

        /** @noinspection NestedTernaryOperatorInspection */
        $info .= (($perms & self::PERM_ALL_EXEC) > 0
            ? (($perms & self::PERM_ALL_EXEC_STICKY) > 0 ? 't' : 'x')
            : (($perms & self::PERM_ALL_EXEC_STICKY) > 0 ? 'T' : '-'));

        return $info;
    }

    /**
     * Removes a directory (and its contents) recursively.
     * Contributed by Askar (ARACOOL) <https://github.com/ARACOOOL>.
     * @param string $dir              The directory to be deleted recursively
     * @param bool   $traverseSymlinks Delete contents of symlinks recursively
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function rmDir(string $dir, bool $traverseSymlinks = true): bool
    {
        if (!\file_exists($dir)) {
            return true;
        }

        if (!\is_dir($dir)) {
            throw new Exception('Given path is not a directory');
        }

        if ($traverseSymlinks || !\is_link($dir)) {
            $list = (array)\scandir($dir, \SCANDIR_SORT_NONE);

            foreach ($list as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $currentPath = "{$dir}/{$file}";

                if (\is_dir($currentPath)) {
                    self::rmDir($currentPath, $traverseSymlinks);
                } elseif (!\unlink($currentPath)) {
                    throw new Exception('Unable to delete ' . $currentPath);
                }
            }
        }

        // Windows treats removing directory symlinks identically to removing directories.
        if (!\defined('PHP_WINDOWS_VERSION_MAJOR') && \is_link($dir)) {
            if (!\unlink($dir)) {
                throw new Exception('Unable to delete ' . $dir);
            }
        } elseif (!\rmdir($dir)) {
            throw new Exception('Unable to delete ' . $dir);
        }

        return true;
    }

    /**
     * Binary safe to open file.
     * @deprecated Use \file_get_contents()
     */
    public static function openFile(string $filepath): ?string
    {
        $contents = null;

        $realPath = \realpath($filepath);
        if ($realPath !== false) {
            $handle = \fopen($realPath, 'r');
            if ($handle !== false) {
                $contents = (string)\fread($handle, (int)\filesize($realPath));
                \fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Quickest way for getting first file line.
     */
    public static function firstLine(string $filepath): ?string
    {
        if (\file_exists($filepath)) {
            $cacheRes = \fopen($filepath, 'r');
            if ($cacheRes !== false) {
                $firstLine = \fgets($cacheRes);
                \fclose($cacheRes);

                return $firstLine === false ? null : $firstLine;
            }
        }

        return null;
    }

    /**
     * Set the writable bit on a file to the minimum value that allows the user running PHP to write to it.
     * @param string $filename The filename to set the writable bit on
     * @param bool   $writable Whether to make the file writable or not
     */
    public static function writable(string $filename, bool $writable = true): bool
    {
        return self::setPerms($filename, $writable, 2);
    }

    /**
     * Set the readable bit on a file to the minimum value that allows the user running PHP to read to it.
     * @param string $filename The filename to set the readable bit on
     * @param bool   $readable Whether to make the file readable or not
     */
    public static function readable(string $filename, bool $readable = true): bool
    {
        return self::setPerms($filename, $readable, 4);
    }

    /**
     * Set the executable bit on a file to the minimum value that allows the user running PHP to read to it.
     * @param string $filename   The filename to set the executable bit on
     * @param bool   $executable Whether to make the file executable or not
     */
    public static function executable(string $filename, bool $executable = true): bool
    {
        return self::setPerms($filename, $executable, 1);
    }

    /**
     * Returns size of a given directory in bytes.
     */
    public static function dirSize(string $dir): int
    {
        $size = 0;

        $flags = \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS;

        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));

        /** @var \SplFileInfo $splFileInfo */
        foreach ($dirIterator as $splFileInfo) {
            if ($splFileInfo->isFile()) {
                $size += (int)$splFileInfo->getSize();
            }
        }

        return $size;
    }

    /**
     * Returns all paths inside a directory.
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public static function ls(string $dir): array
    {
        $contents = [];

        $flags = \FilesystemIterator::KEY_AS_PATHNAME
            | \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS;

        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));

        /** @var \SplFileInfo $splFileInfo */
        foreach ($dirIterator as $splFileInfo) {
            $contents[] = $splFileInfo->getPathname();
        }

        \natsort($contents);

        return $contents;
    }

    /**
     * Nice formatting for computer sizes (Bytes).
     * @param int $bytes    The number in bytes to format
     * @param int $decimals The number of decimal points to include
     */
    public static function format(int $bytes, int $decimals = 2): string
    {
        $exp     = 0;
        $value   = 0;
        $symbols = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $bytes = (float)$bytes;

        if ($bytes > 0) {
            $exp   = (int)\floor(\log($bytes) / \log(1024));
            $value = ($bytes / (1024 ** \floor($exp)));
        }

        if ($symbols[$exp] === 'B') {
            $decimals = 0;
        }

        return \number_format($value, $decimals, '.', '') . ' ' . $symbols[$exp];
    }

    /**
     * Returns extension of file from FS pathname.
     */
    public static function ext(?string $path): string
    {
        if (isStrEmpty($path)) {
            return '';
        }

        if (\str_contains((string)$path, '?')) {
            $path = (string)\preg_replace('#\?(.*)#', '', (string)$path);
        }

        $ext = \pathinfo((string)$path, \PATHINFO_EXTENSION);

        return \strtolower($ext);
    }

    /**
     * Returns name of file with ext from FS pathname.
     */
    public static function base(?string $path): string
    {
        return \pathinfo((string)$path, \PATHINFO_BASENAME);
    }

    /**
     * Returns filename without ext from FS pathname.
     */
    public static function filename(?string $path): string
    {
        return \pathinfo((string)$path, \PATHINFO_FILENAME);
    }

    /**
     * Returns name for directory from FS pathname.
     */
    public static function dirName(?string $path): string
    {
        return \pathinfo((string)$path, \PATHINFO_DIRNAME);
    }

    /**
     * Returns realpath (smart analog of PHP \realpath()).
     */
    public static function real(?string $path): ?string
    {
        if (isStrEmpty($path)) {
            return null;
        }

        $result = \realpath((string)$path);

        return $result === false ? null : $result;
    }

    /**
     * Function to strip trailing / or \ in a pathname.
     * @param null|string $path   the path to clean
     * @param string      $dirSep directory separator (optional)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function clean(?string $path, string $dirSep = \DIRECTORY_SEPARATOR): string
    {
        if (isStrEmpty($path)) {
            return '';
        }

        $path = \trim((string)$path);

        if (($dirSep === '\\') && ($path[0] === '\\') && ($path[1] === '\\')) {
            $path = '\\' . \preg_replace('#[/\\\\]+#', $dirSep, $path);
        } else {
            $path = (string)\preg_replace('#[/\\\\]+#', $dirSep, $path);
        }

        return $path;
    }

    /**
     * Strip off the extension if it exists.
     */
    public static function stripExt(string $path): string
    {
        $reg = '/\.' . \preg_quote(self::ext($path), '') . '$/';

        return (string)\preg_replace($reg, '', $path);
    }

    /**
     * Check is current path directory.
     */
    public static function isDir(?string $path): bool
    {
        if (isStrEmpty($path)) {
            return false;
        }

        $path = self::clean($path);

        return \is_dir($path);
    }

    /**
     * Check is current path regular file.
     */
    public static function isFile(string $path): bool
    {
        $path = self::clean($path);

        return \file_exists($path) && \is_file($path);
    }

    /**
     * Find relative path of file (remove root part).
     */
    public static function getRelative(
        string $path,
        ?string $rootPath = null,
        string $forceDS = \DIRECTORY_SEPARATOR,
    ): string {
        // Cleanup file path
        $cleanedPath = self::clean((string)self::real($path), $forceDS);

        // Cleanup root path
        $rootPath = isStrEmpty($rootPath) ? Sys::getDocRoot() : $rootPath;
        $rootPath = self::clean((string)self::real((string)$rootPath), $forceDS);

        // Remove root part
        $relPath = (string)\preg_replace('#^' . \preg_quote($rootPath, '\\') . '#', '', $cleanedPath);

        return \ltrim($relPath, $forceDS);
    }

    /**
     * Returns clean realpath if file or directory exists.
     */
    public static function isReal(?string $path): bool
    {
        if (isStrEmpty($path)) {
            return false;
        }

        $expected = self::clean((string)self::real($path));
        $actual   = self::clean($path);

        return !isStrEmpty($expected) && $expected === $actual;
    }

    /**
     * Returns true if file is writable.
     */
    private static function setPerms(string $filename, bool $isFlag, int $perm): bool
    {
        $stat = @\stat($filename);

        if ($stat === false) {
            return false;
        }

        // We're on Windows
        if (Sys::isWin()) {
            return true;
        }

        [$myuid, $mygid] = [\posix_geteuid(), \posix_getgid()];

        $isMyUid = $stat['uid'] === $myuid;
        $isMyGid = $stat['gid'] === $mygid;

        if ($isFlag) {
            // Set only the user writable bit (file is owned by us)
            if ($isMyUid) {
                return \chmod($filename, \fileperms($filename) | \intval('0' . $perm . '00', 8));
            }

            // Set only the group writable bit (file group is the same as us)
            if ($isMyGid) {
                return \chmod($filename, \fileperms($filename) | \intval('0' . $perm . $perm . '0', 8));
            }

            // Set the world writable bit (file isn't owned or grouped by us)
            return \chmod($filename, \fileperms($filename) | \intval('0' . $perm . $perm . $perm, 8));
        }

        // Set only the user writable bit (file is owned by us)
        if ($isMyUid) {
            $add = \intval("0{$perm}{$perm}{$perm}", 8);

            return self::chmod($filename, $perm, $add);
        }

        // Set only the group writable bit (file group is the same as us)
        if ($isMyGid) {
            $add = \intval("00{$perm}{$perm}", 8);

            return self::chmod($filename, $perm, $add);
        }

        // Set the world writable bit (file isn't owned or grouped by us)
        $add = \intval("000{$perm}", 8);

        return self::chmod($filename, $perm, $add);
    }

    /**
     * Chmod alias.
     */
    private static function chmod(string $filename, int $perm, int $add): bool
    {
        return \chmod($filename, (\fileperms($filename) | \intval('0' . $perm . $perm . $perm, 8)) ^ $add);
    }
}
