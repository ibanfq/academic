<?php
class MonitorsController extends AppController {
    var $name = 'Monitors';
    var $paginate = array('limit' => 10, 'order' => array('Monitor.name' => 'asc'), 'recursive' => 0);
    
    function index(){
        App::import('Sanitize');
        if (isset($this->params['url']['q'])) {
            $q = Sanitize::escape($this->params['url']['q']);
        } elseif (isset($this->passedArgs['q'])) {
            $q = Sanitize::escape($this->passedArgs['q']);
        } else {
            $q = '';
        }
        $monitors = $this->paginate('Monitor', array('Monitor.name LIKE' => "%$q%"));
        $this->set('monitors', $monitors);
        $this->set('q', $q);
    }
    
    function add(){
        if (!empty($this->data)){
            if ($this->Monitor->save($this->data)){
                $this->Session->setFlash('El monitor se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }

    function view($id = null){
        $this->set('monitor', $this->_findMonitorOrFail($id, array('recursive' => 1)));
    }
    
    function edit($id = null){
        $monitor = $this->_findMonitorOrFail($id, array('recursive' => 1));

        if (empty($this->data['Monitor'])) {
            $this->data = array_intersect_key(
                $monitor,
                array_flip(array('Monitor', 'Classroom', 'MonitorMedia'))
            );
            $this->set('monitor', $monitor);
        } else {
            if (!isset($this->data['Classroom'])) {
                $this->data['Classroom'] = array();
            }
            if (isset($this->data['MonitorMedia'])) {
                $existingMonitorMediaIds = (array) Set::extract("MonitorMedia.{n}.id", $monitor);
                foreach ($this->data['MonitorMedia'] as $dataKey =>  $monitorMediaData) {
                    $mediaId = isset($monitorMediaData['id']) ? $monitorMediaData['id'] : false;
                    $key = $mediaId ? array_search($mediaId, $existingMonitorMediaIds) : false;
                    if ($key !== false) {
                        $this->data['MonitorMedia'][$dataKey]['type'] = $monitor['MonitorMedia'][$key]['type'];
                        $this->data['MonitorMedia'][$dataKey]['src'] = $monitor['MonitorMedia'][$key]['src'];
                        $this->data['MonitorMedia'][$dataKey]['mime_type'] = $monitor['MonitorMedia'][$key]['mime_type'];
                    }
                }
            } else {
                $this->data['MonitorMedia'] = array();
            }
            $dataToSave = ['Monitor' => $this->data['Monitor']];
            
            if ($this->Monitor->save($dataToSave)
                && $this->_saveAssociatedClassrooms($monitor, $this->data)
                && $this->_saveMedia($monitor, $this->data)
            ) {
                $this->Session->setFlash('El monitor se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->Session->setFlash('No se ha podido actualizar correctamente el monitor.');
                $this->data['Monitor'] += $monitor['Monitor'];
                $this->set('monitor', $monitor);
            }
        }
    }
    
    function delete($id = null){
        $monitor = $this->_findMonitorOrFail($id);

        $this->Monitor->delete($monitor['Monitor']['id']);
        $this->Session->setFlash('El monitor ha sido eliminada correctamente');
        
        $this->_removeDir('files/monitors/'.$monitor['Monitor']['id']);

        $this->redirect(array('action' => 'index'));
    }

    function add_media($monitor_id){
        $monitor = $this->_findMonitorOrFail($monitor_id);

        if (!empty($this->data)) {
            $error = false;

            if (empty($this->data['MonitorMedia']['type'])) {
                $this->Session->setFlash('No se ha especificado el tipo de contenido');
                $error = true;
            }

            $mimeType = null;
            $src = null;
            $videoId = null;

            // Check if is a upload and proccess it
            if (!$error && in_array($this->data['MonitorMedia']['type'], array('Imagen', 'Video'), true)) {
                if (!isset($_FILES['data']['error']['MonitorMedia']['src']) || $_FILES['data']['error']['MonitorMedia']['src'] === UPLOAD_ERR_NO_FILE) {
                    $this->Session->setFlash('No se ha especificado el contenido a subir');
                    $error = true;
                }

                if (!$error && $_FILES['data']['error']['MonitorMedia']['src'] !== UPLOAD_ERR_OK) {
                    $this->Session->setFlash('No se ha recibido correctamente el archivo');
                    $error = true;
                }

                if (!$error) {
                    $tmpPath = $_FILES['data']['tmp_name']['MonitorMedia']['src'];
                    $mimeType = $this->Monitor->MonitorMedia->getFileMimeType($tmpPath);

                    switch ($this->data['MonitorMedia']['type']) {
                        case 'Imagen':
                            if (!$this->Monitor->MonitorMedia->isValidImageMimeType($mimeType)) {
                                $this->Session->setFlash('El archivo subido no es una imagen soportada (jpeg, png o gif)');
                                $error = true;
                            }
                            if (intval($this->data['MonitorMedia']['duration']) <= 0) {
                                $this->data['MonitorMedia']['duration'] = 3;
                            }
                            break;
                        case 'Video':
                            if (!$this->Monitor->MonitorMedia->isValidVideoMimeType($mimeType)) {
                                $this->Session->setFlash('El archivo subido no es una video soportada (mp4 o webm)');
                                $error = true;
                            }
                            break;
                    }
                }

                if (!$error) {
                    $srcDir = 'files/monitors/'.$monitor['Monitor']['id'].'/'. md5(time() + rand());
                    $uploadDir = WWW_ROOT . $srcDir;
                    $extension = array(
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'video/mp4' => 'mp4',
                        'video/webm' => 'webm'
                    );
                    $name = pathinfo($_FILES['data']['name']['MonitorMedia']['src'], PATHINFO_FILENAME) . '.' . $extension[$mimeType];
                    $uploadPath = $uploadDir . '/' . $name;

                    if (!file_exists($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                        $this->Session->setFlash('No se ha podido completar la subida del archivo');
                        $error = true;
                    } elseif (!move_uploaded_file($tmpPath, $uploadPath)) {
                        $this->Session->setFlash('No se ha podido completar la subida del archivo');
                        $error = true;
                    } else {
                        $src = $srcDir . '/' . $name;
                    }
                }
            }

            // Check if is a youtube url and process it
            if (!$error && $this->data['MonitorMedia']['type'] === 'Youtube') {
                if (empty($this->data['MonitorMedia']['src']) || !$this->Monitor->MonitorMedia->isValidYoutubeUrl($this->data['MonitorMedia']['src'])) {
                    $this->Session->setFlash('Debes especificar una url de youtube válida');
                    $error = true;
                } else {
                    $src = $this->data['MonitorMedia']['src'];
                    $videoId = $this->Monitor->MonitorMedia->ExtractYoutubeId($src);
                }
            }

            // Check if is a vimeo url and process it
            if (!$error && $this->data['MonitorMedia']['type'] === 'Vimeo') {
                if (empty($this->data['MonitorMedia']['src']) || !$this->Monitor->MonitorMedia->isValidVimeoUrl($this->data['MonitorMedia']['src'])) {
                    $this->Session->setFlash('Debes especificar una url de vimeo válida');
                    $error = true;
                } else {
                    $src = $this->data['MonitorMedia']['src'];
                    $videoId = $this->Monitor->MonitorMedia->ExtractVimeoId($src);
                }
            }

            if (!$error) {
                $order = $this->Monitor->query("SELECT 1 + max(`order`) as `order` FROM monitors_media WHERE monitor_id = '{$monitor['Monitor']['id']}'");
                $data = array(
                    'MonitorMedia' => array(
                        'monitor_id' => $monitor_id,
                        'src' => $src,
                        'mime_type' => $mimeType,
                        'order' => intval($order[0][0]['order']),
                        'video_id' => $videoId,
                    ) + $this->data['MonitorMedia']
                );
                if ($this->Monitor->MonitorMedia->save($data)) {
                    $this->Session->setFlash('El contenido multimedia se ha guardado correctamente');
                    if (empty($this->data['action']['add_and_new'])) {
                        $this->redirect(array('action' => 'view', $monitor_id));
                    } else {
                        $this->redirect(array('action' => 'add_media', $monitor_id));
                    }
                } else {
                    $this->Session->setFlash('No se ha podido guardar el contenido multimedia');
                }
            }
        }

        $this->set('monitor', $monitor);
    }

    function _saveAssociatedClassrooms($monitor, $data){
        $this->loadModel('MonitorsClassroom');

        $existingClassroomsIds = (array) Set::extract("Classroom.{n}.id", $monitor);
        $deletedClassroomsIds = array();
        $newMonitorsClassroomRecords = array();
        foreach ($data['Classroom'] as $classroom) {
            if (!empty($classroom['id'])) {
                if (empty($classroom['show_in_monitor'])) {
                    $deletedClassroomsIds[$classroom['id']] = $classroom['id'];
                } else {
                    if (in_array($classroom['id'], $existingClassroomsIds)) {
                        unset($deletedClassroomsIds[$classroom['id']]);
                    } else {
                        $newMonitorsClassroomRecords[$classroom['id']] = array(
                            'MonitorsClassroom' => array(
                                'monitor_id' => $monitor['Monitor']['id'],
                                'classroom_id' => $classroom['id']
                            )
                        );
                    }
                }
            }
        }

        $return = true;

        if (!empty($deletedClassroomsIds)) {
            $deletedConditions = array(
                'MonitorsClassroom.monitor_id' => $monitor['Monitor']['id'],
                'MonitorsClassroom.classroom_id' => $deletedClassroomsIds,
            );
            
            if (!$this->MonitorsClassroom->deleteAll($deletedConditions)) {
                $return = false;
            };
        }

        if (!empty($newMonitorsClassroomRecords)) {
            if (!$this->MonitorsClassroom->saveAll($newMonitorsClassroomRecords)) {
                $return = false;
            }
        }

        return $return;
    }

    function _saveMedia($monitor, $data){
        $return = true;

        if (isset($data['MonitorMedia'])) {
            $existingMonitorMediaIds = (array) Set::extract("MonitorMedia.{n}.id", $monitor);
            $dataToSave = array();

            foreach ($data['MonitorMedia'] as $monitorMediaData) {
                $id = isset($monitorMediaData['id']) ? $monitorMediaData['id'] : false;
                $key = $id ? array_search($id, $existingMonitorMediaIds) : false;
                if ($key !== false) {
                    $monitorMedia = $monitor['MonitorMedia'][$key];
                    if (empty($monitorMediaData['delete'])) {
                        $dataToSave[] = array_intersect_key(
                            $monitorMediaData,
                            array_flip(
                                $monitorMedia['type'] === 'Imagen'
                                    ? array('order', 'duration', 'visible')
                                    : array('order', 'visible')
                            )
                        ) + $monitorMedia;
                    } elseif ($this->Monitor->MonitorMedia->delete($id)) {
                        if (in_array($monitorMedia['type'], array('Imagen', 'Video'), true)) {
                            $this->_removeDir(dirname($monitorMedia['src']));
                        }
                    } else {
                        $return = false;
                    }

                }
            }

            if (!empty($dataToSave) && !$this->Monitor->MonitorMedia->saveAll($dataToSave)) {
                $return = false;
            }
        }
        return $return;
    }

    function _removeDir($dir){
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    function _findMonitorOrFail($id, $options = null){
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('action' => 'index'));
        }

        $validOptions = array('recursive');
        $conditions = array_intersect_key((array)$options, array_flip($validOptions)) + array(
            'recursive' => -1,
            'conditions' => array('Monitor.id' => $id)
        );

        $monitor = $this->Monitor->find('first', $conditions);

        if (!$monitor) {
            $this->redirect(array('action' => 'index'));
        }

        if (isset($monitor['Classroom'])) {
            $monitor['Classroom'] = set::sort(
                $monitor['Classroom'],
                '{n}.name',
                'asc'
            );
        }

        if (isset($monitor['MonitorMedia'])) {
            $monitor['MonitorMedia'] = set::sort(
                $monitor['MonitorMedia'],
                '{n}.order',
                'asc'
            );
        }

        return $monitor;
    }
    
    function _authorize() {
        parent::_authorize();
        
        $administrator_actions = array('add', 'edit', 'delete');
        $tv_actions = array('index', 'view');
        
        $this->set('section', 'classrooms');
        
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            return false;
        }
    
        if ((array_search($this->params['action'], $tv_actions) === false) && ($this->Auth->user('type') == "Estudiante" || $this->Auth->user('type') == "Profesor")) {
            return false;
        }
    
        return true;
    }
}
