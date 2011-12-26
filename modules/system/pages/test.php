<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 23:48)
 */
//@Entity
class TestPage extends Page {

    function initialize() {
        $this->setTitle("Test");
    }

    function getContent() {
        i18n_strings_unload();
        i18n_strings_find();
        i18n_strings_load();
        _h("Test");
        kernel_out(state_get());
        kernel_out(users_hash('mn32205'));
        //        users_register('milad', 'mn32205', 'Milad', 'Naseri', 'm.m.naseri@gmail.com', '0', 'm');
    }

}

?>