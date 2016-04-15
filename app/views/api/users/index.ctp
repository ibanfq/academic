<?php

echo $this->Api->respond(
    isset($data)? $data : null,
    isset($status)? $status : null,
    isset($code)? $code : null,
    isset($message)? $message : null
);
