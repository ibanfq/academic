<?php
class MonitorsController extends AppController {
    var $name = 'Monitors';
    var $paginate = array('limit' => 10, 'order' => array('Monitor.name' => 'asc'), 'recursive' => 0);
    var $helpers = array('ModelHelper', 'activityHelper', 'Text');
    var $fields_fillable = array('Monitor', 'Classroom', 'MonitorMedia');
    var $fields_guarded = array(
        'Monitor' => ['id', 'institution_id', 'created', 'modified'],
        'MonitorMedia' => ['monitor_id']
    );
    
    function index() {
        App::import('Core', 'Sanitize');
        if (isset($this->params['url']['q'])) {
            $q = Sanitize::escape($this->params['url']['q']);
        } elseif (isset($this->passedArgs['q'])) {
            $q = Sanitize::escape($this->passedArgs['q']);
        } else {
            $q = '';
        }
        $conditions = array(
            'Monitor.institution_id ' => Environment::institution('id'),
            'Monitor.name LIKE' => "%$q%"
        );
        $monitors = $this->paginate('Monitor', $conditions);
        $this->set('monitors', $monitors);
        $this->set('q', $q);
    }
    
    function add(){
        if (!empty($this->data)){
            $this->data = $this->Form->filter($this->data);
            $this->data['Monitor']['institution_id'] = Environment::institution('id');

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

        $this->Monitor->set($monitor);

        if (empty($this->data['Monitor'])) {
            $this->data = array_intersect_key(
                $monitor,
                array_flip(array('Monitor', 'Classroom', 'MonitorMedia'))
            );
        } else {
            $this->data = $this->Form->filter($this->data);
            $this->data['Monitor']['id'] = $monitor['Monitor']['id'];
            $this->data['Monitor']['modified'] = null;

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
                        $this->data['MonitorMedia'][$dataKey]['video_id'] = $monitor['MonitorMedia'][$key]['video_id'];
                    }
                }
            } else {
                $this->data['MonitorMedia'] = array();
            }
            
            if ($this->Monitor->save(array('Monitor' => $this->data['Monitor']))
                && $this->_saveAssociatedClassrooms($monitor, $this->data)
                && $this->_saveMedia($monitor, $this->data)
            ) {
                $this->Session->setFlash('El monitor se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->Session->setFlash('No se ha podido actualizar correctamente el monitor.');
                $this->data['Monitor'] += $monitor['Monitor'];
            }
        }

        $this->set('monitor', $monitor);
    }
    
    function delete($id = null){
        $monitor = $this->_findMonitorOrFail($id);

        $this->Monitor->delete($monitor['Monitor']['id']);
        $this->Session->setFlash('El monitor ha sido eliminada correctamente');
        
        $this->_removeDir('files/monitors/'.$monitor['Monitor']['id']);

        $this->redirect(array('action' => 'index'));
    }

    function board() {
        $this->layout = 'board';
        $this->set('events', $this->_getBoardEvents());
    }

    function show($monitor_id) {
        $ajaxSection = isset($this->params['named']['ajax_section']) ? $this->params['named']['ajax_section'] : null;

        $monitor = !$ajaxSection || $ajaxSection === 'content-media'
            ? $this->_findMonitorOrFail($monitor_id, array('recursive' => 1))
            : $this->_findMonitorOrFail($monitor_id);

        $this->layout = 'board';
        $this->set('monitor', $monitor);

        if ((!$ajaxSection || $ajaxSection === 'content-board') && !empty($monitor['Monitor']['show_events'])) {
            $events = $this->_getBoardEvents(Set::extract("Classroom.{n}.id", $monitor));
            $this->set('events', $events);
        }

        $this->set('ajax_section', $ajaxSection);
    }

    function add_media($monitor_id) {
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
                    $this->Session->setFlash(sprintf(
                        'No se ha recibido correctamente el archivo. (Código de error: %d)',
                        $_FILES['data']['error']['MonitorMedia']['src']
                    ));
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
        $this->set('uploadMaxSize', $this->_getUploadMaxSize());
    }

    function _saveAssociatedClassrooms($monitor, $data) {
        $this->loadModel('MonitorsClassroom');

        $validClassrooms = $this->Monitor->Classroom->find('all', array(
            'conditions' => array(
                'Classroom.institution_id' => Environment::institution('id'),
            ),
            'recursive' => -1
        ));
        $validClassroomsIds = (array) Set::extract("Classroom.{n}.id", $validClassrooms);

        $existingClassroomsIds = (array) Set::extract("Classroom.{n}.id", $monitor);
        $deletedClassroomsIds = array();
        $newMonitorsClassroomRecords = array();
        foreach ($data['Classroom'] as $classroom) {
            if (!empty($classroom['id'])) {
                if (empty($classroom['show_in_monitor'])) {
                    $deletedClassroomsIds[$classroom['id']] = $classroom['id'];
                } elseif (in_array($classroom['id'], $validClassroomsIds)) {
                    $deletedClassroomsIds[$classroom['id']] = $classroom['id'];
                } elseif (in_array($classroom['id'], $existingClassroomsIds)) {
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

    function _saveMedia($monitor, $data) {
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
        if (file_exists($dir)) {
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
    }

    function _findMonitorOrFail($id, $options = null){
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->Session->setFlash('No se ha podido acceder al monitor.');
            $this->redirect(array('action' => 'index'));
        }

        $validOptions = array('recursive');
        $conditions = array_intersect_key((array)$options, array_flip($validOptions)) + array(
            'recursive' => -1,
            'conditions' => array(
                'Monitor.id' => $id,
                'Monitor.institution_id ' => Environment::institution('id'),
            )
        );

        $monitor = $this->Monitor->find('first', $conditions);

        if (!$monitor) {
            $this->Session->setFlash('No se ha podido acceder al monitor.');
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

    function _getBoardEvents($classrooms_ids = null) {
        if (!isset($this->Event)) {
            $this->loadModel('Event');
        }

        if (!isset($this->Booking)) {
            $this->loadModel('Booking');
        }

        $db = $this->Event->getDataSource();

        $classroom_show_tv = Configure::read('app.classroom.show_tv');
        $event_show_tv = !$classroom_show_tv || Configure::read('app.event.show_tv');
        $booking_show_tv = !$classroom_show_tv || Configure::read('app.booking.show_tv');

        $events_filters = array();
        $bookings_filters = array();

        if ($event_show_tv) {
            $events_filters[]= 'Event.show_tv';
        }
        if ($booking_show_tv) {
            $bookings_filters[]= 'Booking.show_tv';
        }

        if (empty($classrooms_ids)) {
            if ($classroom_show_tv) {
                $events_filters []= 'Classroom.show_tv';
                $bookings_filters []= '(Booking.classroom_id = -1 OR Classroom.show_tv)';
            }
        } else {
            $events_filters[]= array('Classroom.id' => $classrooms_ids);
            $bookings_filters[]= array(
                'OR' => array(
                    'Classroom.id' => $classrooms_ids,
                    'Booking.classroom_id = -1'
                )
            );
        }

        $sql1 = $db->buildStatement(
            array(
                'table' => $db->fullTableName($this->Event),
                'alias' => 'Event',
                'fields' => array(
                    'Event.initial_hour',
                    'Event.final_hour',
                    'Activity.name',
                    'Activity.type',
                    'Subject.acronym as subject_acronym',
                    'Subject.level as subject_level',
                    'Group.name as group_name',
                    'Teacher.first_name as teacher_first_name',
                    'Teacher.last_name as teacher_last_name',
                    'Event.classroom_id',
                    'Classroom.name as classroom_name'
                ),
                'conditions' => array(
                    'Event.initial_hour > CURDATE()',
                    'Event.initial_hour < (CURDATE() + INTERVAL 1 DAY)',
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})",
                    $events_filters,
                ),
                'joins' => array(
                    array(
                        'table' => 'classrooms',
                        'alias' => 'Classroom',
                        'type' => 'left',
                        'conditions' => 'Event.classroom_id = Classroom.id'
                    ),
                    array(
                        'table' => 'activities',
                        'alias' => 'Activity',
                        'type' => 'left',
                        'conditions' => 'Event.activity_id = Activity.id'
                    ),
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'left',
                        'conditions' => 'Activity.subject_id = Subject.id'
                    ),
                    array(
                        'table' => 'groups',
                        'alias' => 'Group',
                        'type' => 'left',
                        'conditions' => 'Event.group_id = Group.id'
                    ),
                    array(
                        'table' => 'users',
                        'alias' => 'Teacher',
                        'type' => 'left',
                        'conditions' => 'Event.teacher_id = Teacher.id AND (Teacher.type = "Profesor" OR Teacher.type = "Administrador")'
                    )
                ),
                'order' => null,
                'recursive' => 0,
                'limit' => null,
                'group' => null
            ),
            $this->Event
        );
                    
        $sql2 = $db->buildStatement(
            array(
                'table' => $db->fullTableName($this->Booking),
                'alias' => 'Booking',
                'fields' => array(
                    'Booking.initial_hour',
                    'Booking.final_hour',
                    'Booking.reason as name',
                    '"booking" as type',
                    'null as subject_acronym',
                    'null as subject_level',
                    'null as group_name',
                    'null as teacher_first_name',
                    'null as teacher_last_name',
                    'Booking.classroom_id',
                    'Classroom.name as classroom_name'
                ),
                'conditions' => array(
                    'Booking.initial_hour > CURDATE()',
                    'Booking.initial_hour < (CURDATE() + INTERVAL 1 DAY)',
                    'OR' => array(
                        "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                        "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                    ),
                    $bookings_filters
                ),
                'joins' => array(
                    array(
                        'table' => 'classrooms',
                        'alias' => 'Classroom',
                        'type' => 'left',
                        'conditions' => 'Booking.classroom_id = Classroom.id'
                    )
                ),
                'order' => null,
                'recursive' => 0,
                'limit' => null,
                'group' => null
            ),
            $this->Booking
        );
                    
        $events = $db->fetchAll($sql1.' UNION '.$sql2.' ORDER BY initial_hour, ISNULL(subject_acronym), subject_acronym, name, group_name');

        foreach($events as $i => &$event) {
            $event = $event[0];
            $event['sql_order'] = $i;
        }
        usort($events, array($this, '_sortBoardEvents'));

        return $events;
        
    }

    function _sortBoardEvents($a, $b) {
        if ($a['initial_hour'] === $b['initial_hour']) {
            if ($a['subject_level'] === null || $b['subject_level'] === null) {
                if ($a['subject_level'] === $b['subject_level']) {
                    return strcasecmp($a['name'], $b['name']);
                }
            } else {
                $a_level = $this->Event->Activity->Subject->levelToInt($a['subject_level']);
                $b_level = $this->Event->Activity->Subject->levelToInt($b['subject_level']);
                if ($a_level !== $b_level) {
                    return $a_level - $b_level;
                }
            }
        }
        return $a['sql_order'] - $b['sql_order'];
    }

    function _getUploadMaxSize() {
        $max_size = 0;

        $post_max_size = ini_get('post_max_size');
        $post_max_size = $this->_parseSize(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }
    
        $upload_max = ini_get('upload_max_filesize');
        $upload_max = $this->_parseSize(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }

        return $max_size ?: $this->_parseSize('8MB');
    }
      
    function _parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            $bytes = round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            $bytes = round($size);
        }

        if ($bytes == 0) {
            return "0.00 B";
        }

        $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes, 1024));

        return round($bytes/pow(1024, $e), 2).$s[$e];
    }
    
    function _authorize() {
        parent::_authorize();

        if (! Environment::institution('id')) {
            return false;
        }

        $action = $this->params['action'];
        
        $administrator_actions = array('add', 'edit', 'delete', 'add_media');
        $read_actions = array('index', 'view');
        $public_actions = array('board', 'show');
        
        $this->set('section', 'classrooms');
        
        if ((array_search($action, $administrator_actions) !== false)) {
            return ($this->Auth->user('type') == "Administrador");
        }
    
        if ((array_search($action, $read_actions) !== false)) {
            return ($this->Auth->user('type') != "Estudiante" && $this->Auth->user('type') != "Profesor");
        }

        if ((array_search($action, $public_actions) !== false)) {
            $this->Auth->allow($action);
            return true;
        }
    
        return $this->Acl->check("monitors.{$action}");
    }
}
