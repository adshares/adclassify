<?php
namespace Adclassify\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Aduser\Helper\Utils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Aduser\Entity\User;
use Aduser\Entity\RequestLog;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController extends Controller
{

    /**
     * @Route("/submit", name="submit")
     */
    public function submitAction(Request $request)
    {   
        $response = new JsonResponse();
        
        $content = $request->getContent();
        if (!empty($content))
        {
            $data = json_decode($content, true);
        } else {
            throw new BadRequestHttpException();
        }
        
        $response->setData([
            'request_id' => uniqid(),
            'processed' => false,
        ]);
        
        return $response;
    }

    /**
     * @Route("/get_data/{id}", name="get_data")
     */
    public function dataAction(Request $request, $id)
    {
        $response = new JsonResponse();

        $response->setData([
            'request_id' => $id,
            'processed' => true,
            'keywords' => [
                'category' => 0,
                'safe' => true,
            ],
        ]);
        
        return $response;
    }
    
    /**
     * @Route("/info")
     */
    public function schemaAction(Request $request)
    {
        $response = new JsonResponse();
        
        $schema = <<<EOF
        [
            {
        		"label": "Creative type",
        		"key":"category",
        		"values": [
        					{"label": "Audio Ad (Auto-Play)", "value": "1", "key": "category_1", "parent_label": "Creative type"},
        					{"label": "Audio Ad (User Initiated)", "value": "2", "key": "category_2", "parent_label": "Creative type"},
        					{"label": "In-Banner Video Ad (Auto-Play)", "value": "6", "key": "category_6", "parent_label": "Creative type"},
        					{"label": "In-Banner Video Ad (User Initiated)", "value": "7", "key": "category_7", "parent_label": "Creative type"},
        					{"label": "Provocative or Suggestive Imagery", "value": "9", "key": "category_9", "parent_label": "Creative type"},
        					{"label": "Shaky, Flashing, Flickering, Extreme Animation, Smileys", "value": "10", "key": "category_10", "parent_label": "Creative type"},
        					{"label": "Surveys", "value": "11", "key": "category_11", "parent_label": "Creative type"},
        					{"label": "Text Only", "value": "12", "key": "category_12", "parent_label": "Creative type"},
        					{"label": "User Interactive (e.g., Embedded Games)", "value": "13", "key": "category_13", "parent_label": "Creative type"},
        					{"label": "Windows Dialog or Alert Style", "value": "14", "key": "category_14", "parent_label": "Creative type"},
        					{"label": "Has Audio On/Off Button", "value": "15", "key": "category_15", "parent_label": "Creative type"}
        		],
        		"value_type": "string",
        		"allow_input": true
        	},
        	{
                "label": "Language",
                "key": "lang",
                "values": [
                    {"label": "Polish", "value": "pl", "key": "lang_pol", "parent_label": "Language"},
                    {"label": "English", "value": "en", "key": "lang_en", "parent_label": "Language"},
                    {"label": "Italian", "value": "it", "key": "lang_it", "parent_label": "Language"},
                    {"label": "Japanese", "value": "jp", "key": "lang_jp", "parent_label": "Language"}
                ],
                "value_type": "string",
                "allow_input": false
            }
        ]
EOF;
        $router = $this->container->get('router');
        assert($router instanceof Router);
        
        $response->setData([
                'submit_url' => $router->generate('submit', [] , UrlGeneratorInterface::ABSOLUTE_URL),
                'data_url' => $router->generate('get_data', ['id' => ':id'] , UrlGeneratorInterface::ABSOLUTE_URL),
                'schema' => json_decode($schema)
            ]
        );
        
        return $response;
    }
}
