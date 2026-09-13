<?php

declare(strict_types=1);

namespace App\Services\Mydata;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

final class MydataClient
{
    public function sendInvoice(Company $company, string $xml): MydataResponse
    {
        if (! $company->hasMydataCredentials()) {
            throw new RuntimeException(
                "Η εταιρεία {$company->id} δεν έχει διαπιστευτήρια myDATA."
            );
        }

        $response = Http::withHeaders([
            'aade-user-id' => (string) $company->mydata_user_id,
            'ocp-apim-subscription-key' => (string) $company->mydata_subscription_key,
            'Content-Type' => 'text/xml',
        ])
            ->timeout((int) config('mydata.timeout'))
            ->withBody($xml, 'text/xml')
            ->post(config('mydata.base_url').'/SendInvoices');

        $response->throw();

        return $this->parse($response->body());
    }

    private function parse(string $body): MydataResponse
    {
        $xml = new SimpleXMLElement($body);

        $item = $xml->response[0] ?? null;

        if ($item === null) {
            return MydataResponse::rejected(['Κενή απάντηση από την ΑΑΔΕ.']);
        }

        $status = (string) $item->statusCode;

        if ($status !== 'Success') {
            return MydataResponse::rejected($this->extractErrors($item));
        }

        return MydataResponse::accepted(
            (string) $item->invoiceMark,
            isset($item->invoiceUid) ? (string) $item->invoiceUid : null,
            isset($item->authenticationCode) ? (string) $item->authenticationCode : null,
        );
    }

    /**
     * @return list<string>
     */
    private function extractErrors(SimpleXMLElement $item): array
    {
        $errors = [];

        foreach ($item->errors->error ?? [] as $error) {
            $errors[] = trim((string) $error->code.': '.(string) $error->message);
        }

        return $errors === [] ? ['Απόρριψη χωρίς λεπτομέρειες.'] : $errors;
    }
}
