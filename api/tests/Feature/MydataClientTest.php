<?php

declare(strict_types=1);

use App\Models\Company;
use App\Services\Mydata\MydataClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function companyWithCredentials(): Company
{
    $company = Company::factory()->create();
    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'testkey';
    $company->save();

    return $company;
}

it('parses a successful response', function () {
    Http::fake([
        '*/SendInvoices' => Http::response(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ResponseDoc>
              <response>
                <index>1</index>
                <invoiceUid>9A4E2B1C7D</invoiceUid>
                <invoiceMark>400001912345678</invoiceMark>
                <authenticationCode>ABC123</authenticationCode>
                <statusCode>Success</statusCode>
              </response>
            </ResponseDoc>
            XML, 200),
    ]);

    $result = (new MydataClient)->sendInvoice(companyWithCredentials(), '<InvoicesDoc/>');

    expect($result->accepted)->toBeTrue()
        ->and($result->mark)->toBe('400001912345678')
        ->and($result->uid)->toBe('9A4E2B1C7D')
        ->and($result->authenticationCode)->toBe('ABC123')
        ->and($result->errors)->toBe([]);
});

it('parses a rejection with error details', function () {
    Http::fake([
        '*/SendInvoices' => Http::response(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ResponseDoc>
              <response>
                <index>1</index>
                <errors>
                  <error>
                    <message>Invalid VAT number</message>
                    <code>202</code>
                  </error>
                </errors>
                <statusCode>ValidationError</statusCode>
              </response>
            </ResponseDoc>
            XML, 200),
    ]);

    $result = (new MydataClient)->sendInvoice(companyWithCredentials(), '<InvoicesDoc/>');

    expect($result->accepted)->toBeFalse()
        ->and($result->mark)->toBeNull()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0])->toContain('202');
});

it('sends the required authentication headers', function () {
    Http::fake([
        '*/SendInvoices' => Http::response('<ResponseDoc><response><invoiceMark>1</invoiceMark><statusCode>Success</statusCode></response></ResponseDoc>', 200),
    ]);

    (new MydataClient)->sendInvoice(companyWithCredentials(), '<InvoicesDoc/>');

    Http::assertSent(function ($request) {
        return $request->hasHeader('aade-user-id', 'testuser')
            && $request->hasHeader('ocp-apim-subscription-key', 'testkey');
    });
});

it('throws when the company has no credentials', function () {
    Http::fake();

    $company = Company::factory()->create();

    expect(fn () => (new MydataClient)->sendInvoice($company, '<InvoicesDoc/>'))
        ->toThrow(RuntimeException::class);

    Http::assertNothingSent();
});

it('throws on a network or server error', function () {
    Http::fake([
        '*/SendInvoices' => Http::response('Service Unavailable', 503),
    ]);

    expect(fn () => (new MydataClient)->sendInvoice(companyWithCredentials(), '<InvoicesDoc/>'))
        ->toThrow(RequestException::class);
});
