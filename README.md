<div style="text-align:center">
    <img src="img/files.png" />
</div>

### Simple Example usage:
```php
use function Knobik\Prompts\explorer;

$result = explorer(
    title: 'Hello from a explorer window'
    header: [
        'ID',
        'Name',
        'Email',
    ],
    items: [
        [1, 'John Doe', 'john.doe@example.com'],
        [2, 'Jane Doe', 'jane.doe@example.com'],
        [3, 'Jan Kowalski', 'kowalski@example.com'],
    ],
);
```

### Advanced file explorer example usage:
```php
use Knobik\Prompts\ExplorerPrompt;

function getDirectoryFiles(string $path): array
{
    $files = collect(glob("{$path}/*"))
        ->mapWithKeys(function (string $filename) {
            return [
                $filename => [
                    'filename' => basename($filename),
                    'size' => filesize($filename),
                    'permissions' => sprintf('%o', fileperms($filename)),
                ]
            ];
        });

    if ($path !== '/') {
        $files->prepend([
            'filename' => '..',
            'size' => null,
            'permissions' => sprintf('%o', fileperms(dirname($path)))
        ], dirname($path));
    }

    return $files->toArray();
}

$path = '/var/www/html';
while (true) {
    $path = (new ExplorerPrompt(
        title: $path, //fn(ExplorerPrompt $prompt) => $prompt->highlighted,
        header: [
            'File name',
            'Size in bytes',
            'Permissions'
        ],
        items: $this->getDirectoryFiles($path),
    ))
        ->setColumnOptions(
            column: 2,
            width: 20, // number of characters, null or omit to keep it in auto mode
            align: ColumnAlign::RIGHT
        )
        ->prompt();

    if (is_file($path)) {
        $this->line(file_get_contents($path));
        return self::SUCCESS;
    }
}
```