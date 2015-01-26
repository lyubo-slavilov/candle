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

    protected function stopExecution($message, $code = null, $codeMessage = '')
    {
        throw new AjaxResponseException($message, $code, $codeMessage);
    }

    protected function processActionException(\Exception $e)
    {
        if ($e instanceof AjaxResponseException) {

            $this->getResponse()->setStatus(400, 'USER ERROR');
            if (CANDLE_ENVIRONMENT == 'dev') {
                $data = (object) array(
                    'message' => $e->getMessage(),
                    'dev' => true,
                    'code' => $e->getCode(),
                    'code_message' => 'Manual execution stop',
                    'trace' => str_replace(CANDLE_INSTALL_DIR, '(install dir)', $e->getTraceAsString())
                );
            } else {
                $data = (object) array(
                    'code' => 'Ups.',
                    'code_message' => 'Something went wrong.',
                    'message' => 'We are trying our best to resolve the problem',
                    'trace' => 'Please try again later',
                    'dev' => false,
                );
                
            }

            return $this->json($data, false);
        } else {
            return $e;
        }
    }
}

