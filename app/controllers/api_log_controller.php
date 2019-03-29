<?php
class ApiLogController extends AppController {
    var $name = 'Log';
    var $isApi = true;

    function _authorize() {
        return true;
    }

    function add() {
        try {
            $date = trim($this->Api->getParameter('date'));
            if (preg_match('#^[0-9]+$#', $date)) {
                $clientDate = date('Y-m-d H:i:s', $date);
            } else {
                $clientDate = (new DateTime($date))->format('Y-m-d H:i:s');
            }
        } catch (Exception $e) {
            $clientDate = null;
        }
        $data = array(
            'channel'
                => trim($this->Api->getParameter('channel', [], 'default')),
            'description'
                => trim($this->Api->getParameter('description')),
            'ip'
                => $this->RequestHandler->getClientIP(),
            'content'
                => trim($this->Api->getParameter('content')),
            'client_date'
                => $clientDate,
            'server_date'
                => date('Y-m-d H:i:s')
        );
        
        if (empty($data['description']) && empty($data['content'])) {
            $this->Api->addFail('description', 'required');
        } else {
            if (empty($data['description'])) {
                $data['description'] = substr($data['content'], 0, 256);
            }

            if (strlen($data['description']) > 255) {
                $data['description']
                    = substr($data['description'], 0, -3) . '...';
            }

            if (empty($data['content'])) {
                $data['content'] = null;
            }
            
            App::import('Sanitize');
            $ip = Sanitize::escape($data['ip']);
            $desc = Sanitize::escape($data['description']);
            $from1 = date('Y-m-d H:i:s', strtotime('- 10 min'));
            $from2 = date('Y-m-d H:i:s', strtotime('- 60 min'));
            $count = $this->Log->query(
                "SELECT count('') as total FROM log"
                . " WHERE ip = '$ip' AND description = '$desc'"
                . " AND server_date > '$from1'"
                . " UNION ALL SELECT count('') as total FROM log"
                . " WHERE ip = '$ip' AND description = '$desc'"
                . " AND server_date > '$from2'"
            );
            
            if (intval($count[0][0]['total']) < 5
                && intval($count[1][0]['total']) < 10
            ) {
                if (!$this->Log->save($data)) {
                    $this->Api->setError('No se ha podido crear el registro Log.', 500);
                }
            }
        }

        $this->Api->respond($this);
    }
}
