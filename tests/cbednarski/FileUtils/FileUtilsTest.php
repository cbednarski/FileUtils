<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use cbednarski\FileUtils\FileUtils;

class FileUtilsTest extends PHPUnit_Framework_TestCase
{
    public function testMkdirIfNotExists()
    {
        $path = __DIR__ . '/magicpath';
        $this->assertFileNotExists($path);

        FileUtils::mkdirIfNotExists($path);
        $this->assertFileExists($path);
        $this->assertTrue(is_dir($path));

        rmdir($path);
        $this->assertFileNotExists($path);
    }

    public function testDirIsEmpty()
    {
        $this->assertFalse(FileUtils::dirIsEmpty(__DIR__));

        $path = __DIR__ . '/magicpath';
        FileUtils::mkdirIfNotExists($path);
        $this->assertTrue(FileUtils::dirIsEmpty($path));

        rmdir($path);
        $this->assertFileNotExists($path);
        $this->assertFalse(FileUtils::dirIsEmpty($path));
    }

    public function testExistsAndIsReadable()
    {
        $file = __DIR__ . '/magicfile';
        $this->assertFalse(FileUtils::existsAndIsReadable($file));

        touch($file);
        $this->assertTrue(FileUtils::existsAndIsReadable($file));

        chmod($file, 000);
        $this->assertFalse(FileUtils::existsAndIsReadable($file));

        chmod($file, 700);
        unlink($file);
        $this->assertFileNotExists($file);
    }

    public function testListFilesInDir()
    {
        $path = realpath(__DIR__ . '/../../resources/');
        $files = FileUtils::listFilesInDir($path);

        # Make this test deterministic
        sort($files);

        $expected = array(
            $path . '/some-file.txt',
            $path . '/subfolder/some-other-file.txt',
        );

        $this->assertEquals($expected, $files);
    }

    public function testListFileInMissingDir()
    {
        $path = __DIR__ . '/asdlkfjasdklfjhdsfoiruewr';
        $this->assertFalse(file_exists($path));

        $files = FileUtils::listFilesInDir($path);
        $this->assertEquals(array(), $files);
    }

    public function testMkdirs()
    {
        $path = __DIR__ . '/magicpath/';
        $dirs = array('pie', 'cake', 'icecream');

        FileUtils::mkdirs($dirs, $path);
        $this->assertTrue(is_dir($path));

        foreach ($dirs as $dir) {
            $this->assertTrue(is_dir($path . $dir));
            rmdir($path . $dir);
            $this->assertFalse(is_dir($path . $dir));
        }

        rmdir($path);
        $this->assertFalse(is_dir($path));
    }

    public function testConcat()
    {
        file_put_contents('magicfile1', 'some stuff');
        FileUtils::concat('magicfile2', 'magicfile1');
        $this->assertEquals('some stuff', file_get_contents('magicfile2'));

        FileUtils::concat('magicfile1', 'magicfile2');
        $expected = 'some stuff' . PHP_EOL . 'some stuff';
        $this->assertEquals($expected, file_get_contents('magicfile1'));

        unlink('magicfile1');
        unlink('magicfile2');
    }

    public function testRecursiveDelete()
    {
        FileUtils::mkdirIfNotExists(__DIR__ . '/magicdelete/blah/blee/bloo');

        $file_path = __DIR__ . '/magicdelete/blah/blee/thisisafile';
        touch($file_path);
        $this->assertFileExists($file_path);

        $return = FileUtils::recursiveDelete(__DIR__ . '/magicdelete');
        $this->assertFileNotExists(__DIR__ . '/magicdelete');
        $this->assertEquals(1, $return);
    }

    public function testRecursiveDeleteMissing()
    {
        $path = __DIR__ . '/asdlkfjasdklfjhdsfoiruewr';
        $this->assertFileNotExists($path);

        $return = FileUtils::recursiveDelete($path);
        $this->assertEquals(0, $return);
    }

    public function testPathDiff()
    {
        $path = FileUtils::pathDiff('/path/to/blah', '/path/to/blah/and/some/more');
        $this->assertEquals('/and/some/more', $path);

        $path = FileUtils::pathDiff('/path/to/blah', '/path/to/blah/and/some/more', true);
        $this->assertEquals('and/some/more', $path);

        $path = FileUtils::pathDiff('/path/to/blah', '/path/to/cake');
        $this->assertEquals('/path/to/cake', $path);
    }

    public function testFilterExists()
    {
        $actual = FileUtils::filterExists(array('thisfiledoesntexist', 'northisone'));
        $this->assertEquals(array(), $actual);

        $path = realpath(__DIR__ . '/../../resources');

        $expected = array(
            $path . '/some-file.txt',
        );

        $actual = FileUtils::filterExists(
            array(
                $path . '/magicfalskdjfds',
                $path . '/some-file.txt',
                $path . '/filedoesnotexist.blah',
            )
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRemoveExtension()
    {
        $this->assertEquals('index.html', FileUtils::removeExtension('index.twig', 'twig'));
        $this->assertEquals('index.html', FileUtils::removeExtension('index.html.twig', 'twig'));
        $this->assertEquals('twig.html', FileUtils::removeExtension('twig.twig', 'twig'));
        $this->assertEquals('twig/blah.html', FileUtils::removeExtension('twig/blah.twig', 'twig'));
        $this->assertEquals('twig/twig.html', FileUtils::removeExtension('twig/twig.html.twig', 'twig'));
        $this->assertEquals('twig/twig.html', FileUtils::removeExtension('twig/twig.html.twig', 'twig'));
        $this->assertEquals('.twig.css', FileUtils::removeExtension('.twig.css.twig', 'twig'));
    }

    public function testMatchFilename()
    {
        $filename = 'some/file/you-want-to-match.md';

        $match1 = 'some/file/you-want';
        $this->assertTrue(FileUtils::matchFilename($filename, $match1), 'Basic regex / string match');

        $matches1 = array($match1);
        $this->assertTrue(FileUtils::matchFilename($filename, $matches1), 'Match using an array');

        $matches2 = array('some/file/you-want$');
        $this->assertFalse(FileUtils::matchFilename($filename, $matches2), 'Array match that should fail');

        $this->assertFalse(FileUtils::matchFilename($filename, array()), 'Array match with empty array should fail');
        $this->assertFalse(FileUtils::matchFilename($filename, null), 'Match with null should fail');
        $this->assertTrue(FileUtils::matchFilename($filename, ''), 'Match with empty string should catch everything');
    }

    public function testFileIsHidden()
    {
        $this->assertTrue(FileUtils::fileIsHidden('.'));
        $this->assertTrue(FileUtils::fileIsHidden('.woot.php'));
        $this->assertTrue(FileUtils::fileIsHidden('/root/.idea/woot.php'));
        $this->assertFalse(FileUtils::fileIsHidden('root/hello.php'));
        $this->assertFalse(FileUtils::fileIsHidden('hello.php'));
    }

    public function testSoftRealpath()
    {
        $this->assertEquals("/user/some/path", FileUtils::softRealpath("/user/blah-_0938!#$%&'()+,;=@[]^`{}~/../some/path"));
        $this->assertEquals("/user/blah-_0938!#$%&'()+,;=@[]^`{}~/some/path", FileUtils::softRealpath("/user/blah-_0938!#$%&'()+,;=@[]^`{}~/./some/path"));
        $this->assertEquals("/user/blah-_0938!#$%&'()+,;=@[]^`{}~/some/path", FileUtils::softRealpath("/user/blah-_0938!#$%&'()+,;=@[]^`{}~//some/path"));
        $this->assertEquals("user/some/path", FileUtils::softRealpath("user/blah-_0938!#$%&'()+,;=@[]^`{}~/../some/path"));
        $this->assertEquals("user/blah-_0938!#$%&'()+,;=@[]^`{}~/some/path", FileUtils::softRealpath("user/blah-_0938!#$%&'()+,;=@[]^`{}~/./some/path"));
        $this->assertEquals("user/blah-_0938!#$%&'()+,;=@[]^`{}~/some/path", FileUtils::softRealpath("user/blah-_0938!#$%&'()+,;=@[]^`{}~//some/path"));
    }
}
