<?php

namespace Adshares\Adclassify\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"}, name="index")
     */
    public function index(): Response
    {
        return new RedirectResponse($this->generateUrl('classification_index'));
    }

    /**
     * @Route("/info.{_format}", methods={"GET"}, format="json", requirements={"_format": "json|txt"}, name="info")
     */
    public function info(Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'adclassify',
            'name' => $_ENV['APP_NAME'],
            'version' => $_ENV['APP_VERSION'],
            'adsAddress' => $_ENV['CLASSIFIER_ADS_ACCOUNT'],
            'publicKey' => $_ENV['CLASSIFIER_PUBLIC_KEY'],
        ];

        return new Response(
            $request->getRequestFormat() === 'txt' ? self::formatTxt($info) : self::formatJson($info)
        );
    }

    private static function formatTxt(array $data): string
    {
        $response = '';
        foreach ($data as $key => $value) {
            $key = strtoupper(preg_replace('([A-Z])', '_$0', $key));
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }
            $response .= sprintf("%s=%s\n", $key, $value);
        }

        return $response;
    }

    private static function formatJson(array $data): string
    {
        return json_encode($data);
    }
}
