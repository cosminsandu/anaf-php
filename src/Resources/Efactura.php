<?php

declare(strict_types=1);

namespace Anaf\Resources;

use Anaf\Contracts\FileContract;
use Anaf\Responses\Efactura\CreateMessagesResponse;
use Anaf\ValueObjects\Transporter\Payload;
use Anaf\ValueObjects\Transporter\Xml;
use Exception;
use RuntimeException;

class Efactura
{
    use Concerns\Transportable;

    /**
     * Get the list of messages for a given taxpayer.
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/listamesaje.html
     *
     * @param  array<string, string>  $parameters
     */
    public function messages(array $parameters): CreateMessagesResponse
    {
        $payload = Payload::get('prod/FCTEL/rest/listaMesajeFactura', $parameters);

        /**
         * @var array{eroare?: string, mesaje: array<int, array{data_creare: string, cif: string, id_solicitare: string, detalii: string, tip: string, id: string}>, serial: string, cui: string, titlu: string} $response
         */
        $response = $this->transporter->requestObject($payload);

        if (array_key_exists('eroare', $response)) {
            throw new RuntimeException($response['eroare']);
        }

        return CreateMessagesResponse::from($response);
    }

    /**
     * Get the list of messages for a given taxpayer.
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/descarcare.html
     *
     * @param  array<string, string>  $parameters
     */
    public function download(array $parameters): FileContract
    {
        $payload = Payload::get('prod/FCTEL/rest/descarcare', $parameters);

        return $this->transporter->requestFile($payload);
    }

    /**
     * Convert eFactura from XML to PDF.
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/xmltopdf.html
     *
     * @throws Exception
     */
    public function xmlToPdf(string $xml_path, string $standard = 'FACT1', bool $validate = true): FileContract
    {
        if (! in_array($standard, ['FACT1', 'FCN'])) {
            throw new RuntimeException("Invalid standard {$standard}");
        }

        $validateFile = $validate ? '/DA' : '';

        $payload = Payload::upload(
            resource: "prod/FCTEL/rest/transformare/{$standard}{$validateFile}",
            body: Xml::from($xml_path)->toString(),
        );

        return $this->transporter->requestFile($payload);
    }
}
