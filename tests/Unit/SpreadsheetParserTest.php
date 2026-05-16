<?php

declare(strict_types=1);

use App\Services\Email\SpreadsheetParser;

beforeEach(function () {
    $this->tmp = tempnam(sys_get_temp_dir(), 'sptest_').'.csv';
});

afterEach(function () {
    if (isset($this->tmp) && file_exists($this->tmp)) {
        @unlink($this->tmp);
    }
});

test('parses csv with header row and yields associative rows', function () {
    file_put_contents($this->tmp, "Email,Name,Company\nalice@x.com,Alice,Acme\nbob@y.com,Bob,Beta\n");

    $parser = new SpreadsheetParser($this->tmp, 'csv');
    $preview = $parser->preview(10);

    expect($preview['headers'])->toBe(['Email', 'Name', 'Company']);
    expect($preview['rows'])->toHaveCount(2);
    expect($preview['rows'][0])->toBe(['Email' => 'alice@x.com', 'Name' => 'Alice', 'Company' => 'Acme']);
});

test('skips empty rows in csv', function () {
    file_put_contents($this->tmp, "Email\n\nalice@x.com\n\nbob@y.com\n");

    $parser = new SpreadsheetParser($this->tmp, 'csv');
    $rows = iterator_to_array($parser->stream());

    expect($rows)->toHaveCount(2);
    expect($rows[0]['Email'])->toBe('alice@x.com');
});

test('replaces empty header cell with placeholder', function () {
    file_put_contents($this->tmp, "Email,,Name\na@b.com,extra,Alice\n");

    $parser = new SpreadsheetParser($this->tmp, 'csv');
    $preview = $parser->preview(5);

    expect($preview['headers'])->toBe(['Email', 'column_2', 'Name']);
    expect($preview['rows'][0])->toBe(['Email' => 'a@b.com', 'column_2' => 'extra', 'Name' => 'Alice']);
});

test('rejects unsupported file extension', function () {
    file_put_contents($this->tmp, 'irrelevant');
    new SpreadsheetParser($this->tmp, 'pdf');
})->throws(InvalidArgumentException::class);
