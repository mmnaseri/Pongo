<?php

/**
 * pongo PHP Framework Kernel Component
 * Package STATE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 20:49)
 * @package com.agileapes.pongo.kernel.state
 */

if (!defined("KERNEL_COMPONENT_STATE")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_STATE', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_STATE', 7000);

	class StateException extends KernelException {

		protected function getBase() {
			return KCE_STATE;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	define('STATE_TABLE_NAME', 'http_state');
	define('STATE_COOKIE_NAME', "pongo_STATE_ID");
	define('STATE_COOKIE_ID_PREFIX', "NWSID_");
	define('STATE_COOKIE_KEEP_ALIVE_PERIOD', 3600); //an hour
	define('STATE_GC_AUTO_ADJUST', true);
	define('STATE_GC_MAX', 10000);
	define('GLOBAL_CONTEXT_STATE_VARIABLES', 'state_variables');
	globals_context_register(GLOBAL_CONTEXT_STATE_VARIABLES);

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return void
	 */
	function state_set_option($option, $value) {
		globals_set(GLOBAL_CONTEXT_STATE_VARIABLES, $option, $value);
	}

	/**
	 * @param string $option
	 * @param string $default
	 * @return mixed
	 */
	function state_get_option($option, $default = "") {
		return globals_get(GLOBAL_CONTEXT_STATE_VARIABLES, $option, $default);
	}

	/**
	 * @return string
	 */
	function state_id_get() {
		if (array_key_exists(STATE_COOKIE_NAME, $_COOKIE)) {
			return $_COOKIE[STATE_COOKIE_NAME];
		} else {
			return null;
		}
	}

	/**
	 * @return string
	 */
	function state_id_prefix() {
		$prefix = STATE_COOKIE_ID_PREFIX;
		if (strlen($prefix) > 9) {
			$prefix = substr($prefix, 0, 9);
		}
		return $prefix;
	}

	/**
	 * @return string
	 */
	function state_id_renew() {
		$id = state_id_get();
		if ($id !== null) {
			state_destroy($id);
		}
		$id = uniqid(state_id_prefix(), true);
		$time = 0;
		if (state_get_option("keep-alive", false) === true) {
			$time = time() + intval(kernel_option_get('com.agileapes.pongo.state.validity', STATE_COOKIE_KEEP_ALIVE_PERIOD));
		}
		$dir = url_relative("");
		setcookie(STATE_COOKIE_NAME, $id, $time, $dir, null, null, true);
		$_COOKIE[STATE_COOKIE_NAME] = $id;
		$data = state_defaults();
		db_insert(STATE_TABLE_NAME, array('id' => "'" . $id . "'", 'expiration' => time() + intval(kernel_option_get('com.agileapes.pongo.state.validity', STATE_COOKIE_KEEP_ALIVE_PERIOD)), 'data' => db_quote($data), 'client' => "'" . $_SERVER["REMOTE_ADDR"] . "'"));
		return $id;
	}

	function state_defaults() {
		$algorithm = MCRYPT_RIJNDAEL_256;
		return strings_serialize(array(
			'enc-key' => uniqid("enc-key-"),
			'enc-alg' => $algorithm,
			'enc-rand' => rand(0, 255)
		));
	}

	/**
	 * @param string $id
	 * @return void
	 */
	function state_destroy($id = null) {
		if ($id === null) {
			$id = state_id_get();
		}
		db_delete(STATE_TABLE_NAME, array('id' => "'" . $id . "'"));
		state_set_option('data', state_defaults());
		$dir = url_relative("");
		setcookie(STATE_COOKIE_NAME, false, time() - 3600, $dir, null, null, true);
	}

	/**
	 * @return void
	 */
	function state_initialize() {
		if (!db_table_exists(STATE_TABLE_NAME)) {
			db_table_create(STATE_TABLE_NAME, array('id' => db_create_column(CT_VARCHAR, 32, false), 'expiration' => db_create_column(CT_INT, null, false), 'data' => db_create_column(CT_TEXT, DATABASE_DEFAULT_LENGTH, false, "''"), 'client' => db_create_column(CT_VARCHAR, 15, false, "'0.0.0.0'")), DATABASE_TABLE_FAST);
		}
		$rand = rand(0, STATE_GC_MAX);
		if ($rand < kernel_option_get('com.agileapes.pongo.state.gc', STATE_GC_MAX)) {
			state_gc();
		}
		$id = state_id_get();
		if ($id === null) {
			$id = state_id_renew();
		}
		state_fetch($id);
	}

	/**
	 * @param  string $id
	 * @return void
	 */
	function state_fetch($id = null) {
		if ($id === null) {
			$id = state_id_get();
		}
		$data = db_load(STATE_TABLE_NAME, array('id' => "'" . $id . "'"));
		if (count($data) > 0) {
			$data = $data[0];
			if ($data['client'] != $_SERVER["REMOTE_ADDR"]) {
				$old = array('id' => $id, 'data' => $data);
				$id = state_id_renew();
				$data = null;
				state_fetch($id);
				log_warning("Possible identity theft occurred. (Found '$_SERVER[REMOTE_ADDR]' while expected '" . $old['data']['client'] . "' on state id $old[id])", "security");
			}
			if ($data['expiration'] <= time()) {
				$id = state_id_renew();
				state_fetch($id);
				return;
			}
			state_set_option("data", strings_deserialize($data['data']));
		} else if ($id !== null) {
			state_id_renew();
			state_fetch();
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	function state_set($key, $value) {
		$data = state_get_option('data', null);
		if (array_search($key, array('enc-key', 'enc-alg', 'enc-rand')) !== false) {
			throw new StateException("Cannot modify reserved entry");
		}
		if ($data === null) {
			throw new StateException("State not initialized");
		}
		$data[$key] = $value;
		state_set_option('data', $data);
	}

	function state_initialized() {
		return state_get_option('data', null) !== null;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function state_get($key = null, $default = null) {
		$data = state_get_option('data', null);
		if ($data === null) {
			throw new StateException("State not initialized");
		}
		if ($key === null) {
			return $data;
		}
		if (array_key_exists($key, $data)) {
			return $data[$key];
		} else {
			return $default;
		}
	}

	/**
	 * @return void
	 */
	function state_gc() {
		//adjusting garbage collection probability
		$probability = STATE_GC_MAX / 2; //P(gc)=0.5
		if (STATE_GC_AUTO_ADJUST) {
			$probability = state_gc_probability(state_count(array('expiration' => array('>', time() + intval(kernel_option_get('com.agileapes.pongo.state.validity', STATE_COOKIE_KEEP_ALIVE_PERIOD)) - 3600)))) * STATE_GC_MAX;
		}
		kernel_option_set('options.state.gc', $probability);
		//collecting garbage
		db_delete(STATE_TABLE_NAME, array('expiration' => array('<', time())));
	}

	/**
	 * @return void
	 */
	function state_commit() {
		$data = state_get_option('data', null);
		if ($data === null) {
			throw new StateException("State not initialized");
		}
		$id = state_id_get();
		db_update(STATE_TABLE_NAME, array('expiration' => time() + intval(kernel_option_get('com.agileapes.pongo.state.validity', STATE_COOKIE_KEEP_ALIVE_PERIOD)), 'data' => db_quote(strings_serialize($data)), 'client' => "'" . $_SERVER["REMOTE_ADDR"] . "'"), array('id' => "'" . $id . "'"));
	}

	/**
	 * @return mixed
	 */
	function state_list() {
		$data = db_load(STATE_TABLE_NAME, array('expiration' => array('>', time())));
		for ($i = 0; $i < count($data); $i++) {
			$data[$i]['data'] = strings_deserialize($data[$i]['data']);
		}
		return $data;
	}

	/**
	 * @param string|Closure $callback
	 * @param mixed $arguments
	 * @return void
	 */
	function state_visit_all($callback, &$arguments = null) {
		$data = state_list();
		for ($i = 0; $i < count($data); $i++) {
			$item = $data[$i];
			$callback($item, $arguments);
		}
	}

	/**
	 * @param array $criteria
	 * @return int
	 */
	function state_count($criteria = array()) {
		$count = db_load(array(STATE_TABLE_NAME => 'count(*) as cnt'), $criteria);
		return intval($count[0]['cnt']);
	}

	/**
	 *					 1
	 * p = ---------------------------------
	 *	   [e^(0.01*v^0.2)] * (1+log(v))
	 * @param  int $visitors
	 * @return float
	 */
	function state_gc_probability($visitors) {
		$p = exp(-0.01 * pow($visitors, 0.2));
		//        $p = $p * pow(10, -log10(1 + log10($visitors)));
		$p = $p / (1 + log10($visitors));
		$p = (float) ceil(STATE_GC_MAX * $p) / STATE_GC_MAX;
		return $p;
	}

	function state_mcrypt_create_iv($size, $source) {
		$iv = '';
		for ($i = 0; $i < $size; $i++) {
			$iv .= chr($source);
		}
		return $iv;
	}

	function state_create_iv() {
		return state_mcrypt_create_iv(mcrypt_get_iv_size(state_get('enc-alg'), MCRYPT_MODE_ECB), state_get('enc-rand'));
	}

	function state_encrypt($string) {
		return bin2hex(mcrypt_encrypt(state_get('enc-alg'), state_get('enc-key'), $string, MCRYPT_MODE_CBC, state_create_iv()));
	}

	function state_decrypt($string) {
		return mcrypt_decrypt(state_get('enc-alg'), state_get('enc-key'), hex2bin($string), MCRYPT_MODE_CBC, state_create_iv());
	}

}

?>