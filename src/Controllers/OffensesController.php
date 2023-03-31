<?php

namespace Vanier\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// exceptions
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Vanier\Api\exceptions\HttpNotFound;
use Vanier\Api\exceptions\HttpBadRequest;
use Vanier\Api\exceptions\HttpUnprocessableContent;
// helpers
use Vanier\Api\Helpers\ValidateHelper;
// models
use Vanier\Api\models\OffensesModel;

/**
 * Summary of OffensesController
 */
class OffensesController extends BaseController
{

    private $offenses_model = null;

    private array $FILTER_PARAMS = [
        'description', 'classification', 'name',
        'sort_by', 'page', 'pageSize',
    ];

    /**
     * Summary of __construct
     */
    public function __construct()
    {
        $this->offenses_model = new OffensesModel();
    }

    /**
     * Summary of handleOffenses
     * @param Request $request
     * @param Response $response
     * @throws HttpUnprocessableContent
     * @throws HttpBadRequest
     * @throws HttpNotFound
     * @return Response
     */
    public function handleOffenses(Request $request, Response $response)
    {
        // constant values
        define('DEFAULT_PAGE', 1);
        define("DEFAULT_PAGE_SIZE", 10);

        // filter by title
        $filters = $request->getQueryParams();

        // validate filters
        if ($filters) {
            foreach ($filters as $key => $value) {
                if (!ValidateHelper::validateParams($key, $this->FILTER_PARAMS)) {
                    throw new HttpUnprocessableContent($request, 'Invalid query parameter: ' . ' {' . $key . '}');
                } elseif (strlen($value)  == 0) {
                    throw new HttpUnprocessableContent($request, 'Please provide query value for : ' . '{' . $key . '}');
                }
            }
        }

        // verify if client added a page and pageSize params
        // if client didn't add a page and pageSize params, paginate using the default values
        $page = $filters["page"] ?? DEFAULT_PAGE;
        $pageSize = $filters["pageSize"] ?? DEFAULT_PAGE_SIZE;

        // check if the params is numeric, if not throw a bad request error
        if (!ValidateHelper::validatePageNumbers($page, $pageSize)) {
            throw new HttpBadRequest($request, "expected numeric but received alpha");
        }
        $dataParams = ['page' => $page, 'pageSize' => $pageSize, 'pageMin' => 1, 'pageSizeMin' => 5, 'pageSizeMax' => 10];
        // check if page is within in range else throw unprocessable content
        if (!ValidateHelper::validatePagingParams($dataParams)) {
            throw new HttpUnprocessableContent($request, "Out of range, unable to process your request, please consult the documentation");
        }


        $this->offenses_model->setPaginationOptions($page, $pageSize);

        // catch any DB exceptions
        try {
            $data = $this->offenses_model->getOffenses($filters);
        } catch (Exception $e) {
            throw new HttpBadRequest($request, "Invalid request Syntax, please refer to the documentation");
        }
        // throw a HttpNotFound error if data is empty
        if (!$data['offenses']) {
            throw new HttpNotFound($request, 'please check you parameter or consult the documentation');
        }

        // return parsed data
        return $this->preparedResponse($response, $data, StatusCodeInterface::STATUS_OK);
    }
}
