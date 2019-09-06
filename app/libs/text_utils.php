<?php

/**
 * TextUtils class
 */
class TextUtils extends Object {

    function maskdni($dni = null)
    {
        $output = trim($dni);

        if (! empty($output)) {
            return '****' . substr($output, -4);
        }

        return $output;
    }
}
