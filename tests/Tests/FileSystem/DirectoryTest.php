<?php

namespace Eufony\Tests\FileSystem;

use Eufony\Utils\Config\Config;
use Eufony\Utils\FileSystem\Directory;
use Eufony\Utils\FileSystem\File;
use Eufony\Utils\FileSystem\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Eufony\Utils\FileSystem\Directory
 * @uses   \Eufony\Utils\FileSystem\File
 * @uses   \Eufony\Utils\FileSystem\Path
 * @uses   \Eufony\Utils\Config\Config
 */
class DirectoryTest extends TestCase {

    public function assertArrayEqualsIgnoreOrder(array $expected, array $actual): void {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::exists
     */
    public function testExists(): void {
        $this->assertTrue(Directory::exists('tests/assets'));
        $this->assertFalse(Directory::exists('tests/assets/sample.txt'));
        $this->assertFalse(Directory::exists('foo/bar'));
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::make
     * @covers \Eufony\Utils\FileSystem\Directory::remove
     */
    public function testMakeAndRemove(): void {
        $this->assertFalse(Directory::exists('tests/tmp/foo'));
        Directory::make('tests/tmp/foo/bar');
        $this->assertTrue(Directory::exists('tests/tmp/foo'));
        File::touch('tests/tmp/foo/bar/baz.txt');
        Directory::remove('tests/tmp/foo');
        $this->assertFalse(Directory::exists('tests/tmp/foo'));
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::isEmpty
     */
    public function testEmpty(): void {
        $dir = 'tests/tmp/foo';
        Directory::make($dir);
        $this->assertTrue(Directory::isEmpty($dir));
        File::touch("$dir/test.txt");
        $this->assertFalse(Directory::isEmpty($dir));
        Directory::remove($dir);
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::list
     */
    public function testListValidNonRecursive(): void {
        $paths = [
            'json',
            'sample.txt',
            'protected.txt',
            'symlink.txt',
        ];
        $app_dir = Config::get('APP_DIR');
        $paths = array_map(fn($path) => "$app_dir/tests/assets/$path", $paths);
        $this->assertArrayEqualsIgnoreOrder($paths, Directory::list('tests/assets', recursive: false));
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::list
     */
    public function testListValidRecursive(): void {
        $paths = [
            'json',
            'json/sample.json',
            'sample.txt',
            'protected.txt',
            'symlink.txt',
        ];
        $app_dir = Config::get('APP_DIR');
        $paths = array_map(fn($path) => "$app_dir/tests/assets/$path", $paths);
        $this->assertArrayEqualsIgnoreOrder($paths, Directory::list('tests/assets', recursive: true));
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::list
     */
    public function testListInvalid(): void {
        $this->expectException(IOException::class);
        $this->expectExceptionMessageMatches('/Directory not found/');
        Directory::list('foo/bar');
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::files
     */
    public function testFiles(): void {
        $files = [
            'sample.txt',
            'protected.txt',
            'symlink.txt',
        ];
        $app_dir = Config::get('APP_DIR');
        $files = array_map(fn($file) => "$app_dir/tests/assets/$file", $files);
        $this->assertArrayEqualsIgnoreOrder($files, Directory::files('tests/assets', recursive: false));
    }

    /**
     * @covers \Eufony\Utils\FileSystem\Directory::subdirs
     */
    public function testSubdirs(): void {
        $directories = [
            'json',
        ];
        $app_dir = Config::get('APP_DIR');
        $directories = array_map(fn($dir) => "$app_dir/tests/assets/$dir", $directories);
        $this->assertArrayEqualsIgnoreOrder($directories, Directory::subdirs('tests/assets', recursive: true));
    }

}