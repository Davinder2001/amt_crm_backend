<?php

if (!function_exists('sessionHelper')) {
    /**
     * Get or set a session value globally.
     *
     * @param string|null $key
     * @param mixed|null $value
     * @return mixed|\Illuminate\Session\Store
     */
    function sessionHelper($key = null, $value = null)
    {
        if (is_null($key)) {
            return session();
        }

        if (is_null($value)) {
            return session($key);
        }

        session([$key => $value]);
    }
}
