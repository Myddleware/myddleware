<?php

namespace Myddleware\ApiBundle\v1_0\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    public function indexAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($data);

    } 
	
	public function generateDocumentsAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($data);

    }
}
