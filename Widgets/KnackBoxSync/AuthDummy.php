<?php

/**
 * this is a dummy auth class, that allows testing components that would otherwise fail due
 * to invalid user authorization. generally php unit runs as a guest user.
 * @author nblackwe
 *
 */
if (!class_exists('core\DataType')) {

    throw new Exception('include core datatype');
}

class AuthDummy extends core\DataType implements AttributesAuthenticator {

    public function authorize($task, $item) {

        return true;
    }
}