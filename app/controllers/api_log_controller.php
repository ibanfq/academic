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
            'short_description'
                => trim($this->Api->getParameter('short_description')),
            'ip'
                => $this->RequestHandler->getClientIP(),
            'text'
                => trim($this->Api->getParameter('text')),
            'client_date'
                => $clientDate,
            'server_date'
                => date('Y-m-d H:i:s')
        );
        
        if (empty($data['short_description']) && empty($data['text'])) {
            $this->Api->addFail('short_description', 'required');
        } else {
            if (empty($data['short_description'])) {
                $data['short_description'] = substr($data['text'], 0, 256);
            }

            if (strlen($data['short_description']) > 255) {
                $data['short_description']
                    = substr($data['short_description'], 0, -3) . '...';
            }

            if (empty($data['text'])) {
                $data['text'] = null;
            }
            
            App::import('Sanitize');
            $ip = Sanitize::escape($data['ip']);
            $desc = Sanitize::escape($data['short_description']);
            $from1 = date('Y-m-d H:i:s', strtotime('- 10 min'));
            $from2 = date('Y-m-d H:i:s', strtotime('- 60 min'));
            $count = $this->Log->query(
                "SELECT count('') as total FROM log"
                . " WHERE ip = '$ip' AND short_description = '$desc'"
                . " AND server_date > '$from1'"
                . " UNION ALL SELECT count('') as total FROM log"
                . " WHERE ip = '$ip' AND short_description = '$desc'"
                . " AND server_date > '$from2'"
            );
            
            if (intval($count[0][0]['total']) < 5
                && intval($count[1][0]['total']) < 10
            ) {
                if (!$this->Log->save($data)) {
                    $this->Api->setError('No se ha podido crear el registro Log.');
                }
            }
        }

        $this->Api->respond($this);
    }
}
