<?php

namespace Myddleware\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    public function indexAction(Request $request)
    {
        $data = $request->request->all();

        // return new JsonResponse($request);
        // return new JsonResponse($request->get('enterprise'));
        // return new JsonResponse(print_r($request->request,true));
        // return new JsonResponse('test');
        return new JsonResponse($data);

    }
}
