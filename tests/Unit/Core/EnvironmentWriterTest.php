<?php

namespace Tests\Unit\Core;

use App\Core\Installer\EnvironmentWriter;
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvironmentWriterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/env-writer-'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', array_filter(glob($this->directory.'/{,.}*', GLOB_BRACE) ?: [], 'is_file'));
        @rmdir($this->directory);
    }

    public function test_it_replaces_existing_keys_and_keeps_other_lines(): void
    {
        file_put_contents($this->directory.'/.env', "# komentár\nAPP_NAME=Old\nDB_HOST=127.0.0.1\n");

        (new EnvironmentWriter($this->directory.'/.env'))->set(['APP_NAME' => 'New', 'DB_PORT' => '3306']);

        $this->assertSame("# komentár\nAPP_NAME=New\nDB_HOST=127.0.0.1\nDB_PORT=3306\n", file_get_contents($this->directory.'/.env'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function trickyValues(): array
    {
        return [
            'spaces' => ['Kaviareň u Jožka'],
            'double quotes' => ['He said "hi"'],
            'dollar' => ['pa$$word${HOME}'],
            'backslash' => ['C:\\path\\to'],
            'hash' => ['secret#123'],
            'single quote' => ["it's"],
            'equals' => ['a=b'],
            'keyword' => ['null'],
        ];
    }

    #[DataProvider('trickyValues')]
    public function test_written_values_are_read_back_unchanged(string $value): void
    {
        $path = $this->directory.'/.env';
        (new EnvironmentWriter($path))->set(['SOME_VALUE' => $value]);

        $parsed = Dotenv::parse((string) file_get_contents($path));

        $this->assertSame($value, $parsed['SOME_VALUE']);
    }

    public function test_it_rejects_invalid_keys(): void
    {
        $this->expectException(\RuntimeException::class);

        (new EnvironmentWriter($this->directory.'/.env'))->set(['bad key' => 'x']);
    }
}
