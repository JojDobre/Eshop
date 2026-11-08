<?php

namespace Tests\Feature\Shop;

use App\Models\CustomerGroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Modules\Transfer\Exports\ExportRunner;
use Modules\Transfer\Filament\Pages\DataExport;
use Modules\Transfer\Filament\Pages\DataImportPage;
use Modules\Transfer\Imports\Importers;
use Modules\Transfer\Imports\ImportRunner;
use Modules\Transfer\Imports\SpreadsheetReader;
use Modules\Transfer\Models\DataImport;
use Modules\Transfer\Models\ExportTemplate;
use Modules\Transfer\ScheduledExports;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class TransferTest extends ShopTestCase
{
    private function csv(string $content, string $name = 'import.csv'): string
    {
        File::ensureDirectoryExists(storage_path('app/private/imports'));
        File::put(storage_path('app/private/imports/'.$name), $content);

        return 'imports/'.$name;
    }

    private function import(string $entity, string $file): DataImport
    {
        $runner = app(ImportRunner::class);
        $path = storage_path('app/private/'.$file);

        return DataImport::query()->create([
            'entity' => $entity, 'file_path' => $file, 'file_name' => basename($file), 'status' => 'running',
            'total_rows' => SpreadsheetReader::count($path),
            'mapping' => $runner->guessMapping(app(Importers::class)->find($entity), SpreadsheetReader::header($path)),
        ]);
    }

    public function test_exports_in_all_formats_with_encoding_and_columns(): void
    {
        $category = $this->category('Káva');
        $this->product('Etiópia Šťavnatá', '12.90', 5, $category, ['sku' => 'ETI-1', 'ean' => '8586000000017']);
        $runner = app(ExportRunner::class);

        $csv = $runner->run(['entity' => 'products', 'format' => 'csv', 'columns' => ['sku', 'name', 'price', 'categories'], 'delimiter' => ';', 'encoding' => 'UTF-8']);
        $content = file_get_contents($csv['path']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('ETI-1;"Etiópia Šťavnatá";12.90;Káva', $content);

        $win = $runner->run(['entity' => 'products', 'format' => 'csv', 'columns' => ['name'], 'encoding' => 'Windows-1250', 'delimiter' => ',']);
        $this->assertStringContainsString(iconv('UTF-8', 'CP1250', 'Etiópia Šťavnatá'), file_get_contents($win['path']));

        $json = json_decode(file_get_contents($runner->run(['entity' => 'products', 'format' => 'json', 'columns' => ['sku', 'stock_quantity']])['path']), true);
        $this->assertSame([['sku' => 'ETI-1', 'stock_quantity' => 5]], $json);

        $xml = simplexml_load_file($runner->run(['entity' => 'products', 'format' => 'xml', 'columns' => ['sku', 'ean']])['path']);
        $this->assertSame('8586000000017', (string) $xml->item->ean);

        $xlsx = $runner->run(['entity' => 'products', 'format' => 'xlsx', 'columns' => ['sku', 'name']]);
        $this->assertSame(['ETI-1', 'Etiópia Šťavnatá'], array_values(iterator_to_array(SpreadsheetReader::rows($xlsx['path']))[0]));
    }

    public function test_product_import_with_categories_variants_and_errors(): void
    {
        $this->product('Starý názov', '1.00', attributes: ['sku' => 'KAVA-1']);
        $file = $this->csv("SKU;Názov;Cena;Sklad;Kategórie;SKU hlavného produktu;Voľby variantu\n"
            ."KAVA-1;Etiópia;12,90;7;Káva > Zrnková;;\n"
            ."TRICKO;Tričko;19.90;0;Oblečenie;;\n"
            ."TRICKO-M;;21.00;3;;TRICKO;\"Veľkosť=M; Farba=čierna\"\n"
            ."ZLE;Zlý;abc;1;;;\n"
            .";Bez SKU;1;1;;;\n");

        $import = $this->import('products', $file);
        $this->assertSame(['SKU' => 'sku', 'Názov' => 'name', 'Cena' => 'price', 'Sklad' => 'stock_quantity', 'Kategórie' => 'categories', 'SKU hlavného produktu' => 'parent_sku', 'Voľby variantu' => 'variant_options'], $import->mapping);
        $this->assertCount(5, app(ImportRunner::class)->preview($import));

        $this->assertTrue(app(ImportRunner::class)->process($import));
        $import->refresh();

        $this->assertSame('done', $import->status);
        $this->assertSame(2, $import->created_count);
        $this->assertSame(1 + 0, $import->updated_count);
        $this->assertCount(2, $import->errors);
        $this->assertStringContainsString('Riadok 5', $import->errors[0]);

        $coffee = Product::query()->where('sku', 'KAVA-1')->sole();
        $this->assertSame('Etiópia', $coffee->name);
        $this->assertSame(1290, $coffee->enteredPriceIn('EUR'));
        $this->assertSame(7, $coffee->stock_quantity);
        $this->assertSame('Káva › Zrnková', $coffee->categories->first()->pathName());

        $shirt = Product::query()->where('sku', 'TRICKO')->sole();
        $variant = $shirt->variants()->with('values')->sole();
        $this->assertSame('TRICKO-M', $variant->sku);
        $this->assertSame(3, $variant->stock_quantity);
        $this->assertSame('M / čierna', $variant->name());
        $this->assertSame(1, Category::query()->get()->filter(fn ($category) => $category->name === 'Káva')->count());
    }

    public function test_customer_import_from_xlsx_and_chunking(): void
    {
        $group = CustomerGroup::query()->create(['name' => 'VIP', 'code' => 'vip']);
        Customer::query()->create(['name' => 'Jana', 'email' => 'jana@example.com', 'password' => 'tajne123']);
        File::ensureDirectoryExists(storage_path('app/private/imports'));
        $path = storage_path('app/private/imports/customers.xlsx');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['email', 'name', 'group', 'city', 'country']));
        $writer->addRow(Row::fromValues(['JANA@example.com', 'Jana Nováková', 'vip', 'Brno', 'cz']));
        $writer->addRow(Row::fromValues(['peter@example.com', 'Peter', '', 'Žilina', 'SK']));
        $writer->addRow(Row::fromValues(['zly-email', 'X', '', '', '']));
        $writer->close();

        $import = $this->import('customers', 'imports/customers.xlsx');
        // Nulový časový limit: v každej požiadavke sa spracuje iba časť.
        $this->assertFalse(app(ImportRunner::class)->process($import, 0.0));
        $this->assertSame('running', $import->fresh()->status);

        while (! app(ImportRunner::class)->process($import->fresh())) {
            // Pokračuje, kým nie je hotový.
        }

        $import->refresh();
        $this->assertSame(1, $import->created_count);
        $this->assertSame(1, $import->updated_count);
        $jana = Customer::query()->where('email', 'jana@example.com')->sole();
        $this->assertSame('Jana Nováková', $jana->name);
        $this->assertSame($group->id, $jana->customer_group_id);
        $this->assertSame('CZ', $jana->billing_address['country']);
        $this->assertSame('Žilina', Customer::query()->where('email', 'peter@example.com')->sole()->billing_address['city']);
    }

    public function test_admin_pages_templates_and_scheduled_exports(): void
    {
        Mail::fake();
        $this->product('Káva', attributes: ['sku' => 'K1']);
        $this->actingAs($this->superAdmin());
        $this->get('/admin/export')->assertOk();
        $this->get('/admin/import')->assertOk();

        Livewire::test(DataExport::class)
            ->set('data.entity', 'products')->set('data.format', 'xlsx')->set('data.columns', ['sku', 'name'])
            ->callAction('download')->assertHasNoErrors()
            ->callAction('saveTemplate', data: ['name' => 'Produkty mesačne', 'schedule' => 'monthly', 'email' => 'uctovnik@example.com'])
            ->assertHasNoActionErrors();

        $template = ExportTemplate::query()->sole();
        $this->assertSame('xlsx', $template->config['format']);
        $this->assertSame(1, app(ScheduledExports::class)->send());
        $this->assertSame(0, app(ScheduledExports::class)->send());

        File::ensureDirectoryExists(storage_path('app/private/imports'));
        Livewire::test(DataImportPage::class)
            ->set('data.entity', 'products')
            ->set('data.file', UploadedFile::fake()->createWithContent('produkty.csv', "sku;name;price\nNOVY;Nový produkt;5\n"))
            ->call('upload')
            ->assertSee('Nový produkt')
            ->call('start');

        $this->assertSame('Nový produkt', Product::query()->where('sku', 'NOVY')->value('name'));
    }
}
