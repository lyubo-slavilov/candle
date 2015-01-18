<?php
namespace Candle\Controller;

abstract class AbstractAjaxController extends AbstractController
{

    abstract protected function getHttpActionList();

    public function beforeExecute()
    {
        parent::beforeExecute();

        $httpActions = $this->getHttpActionList();

        $action = $this->getCurrentAction();

        if (! in_array($action, $httpActions)) {
            $this->prepareForAjaxRequest();
        }
    }


    public function afterExecute($result)
    {
        $httpActions = $this->getHttpActionList();

        $action = $this->getCurrentAction();

        if (! in_array($action, $httpActions)) {
            $data = array(
                'result' => $result
            );

            return $this->json((object) $data, true);
        }

        return parent::afterExecute($result);


    }
    protected function json(\stdClass $data, $success = true)
    {
        $status = $success ? 'ok' : 'error';
        $data->status = $status;
        return json_encode($data);
    }

    private function prepareForAjaxRequest()
    {

        // this do not work on dev19.... why???!?
        // if (!$this->getRequest()->isAjax()) {
        // $this->raise404Error();
        // }
        $this->setTemplate(false);
        $this->setLayout(false);
    }

    protected function stopExecution($message, $code = null)
    {
        throw new AjaxResponseException($message, $code);
    }

    protected function processActionException(\Exception $e)
    {
        if ($e instanceof AjaxResponseException) {

            $this->getResponse()->setStatus(400, 'USER ERROR');

            $data = (object) array(
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            );

            return $this->json($data, false);
        } else {
            return $e;
        }
    }
}

