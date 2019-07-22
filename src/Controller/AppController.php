<?php

namespace Adshares\Adclassify\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{

    public function index(): Response
    {
        return $this->render('app/index.html.twig', []);
    }

    public function info(Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'adclassify',
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION'),
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
