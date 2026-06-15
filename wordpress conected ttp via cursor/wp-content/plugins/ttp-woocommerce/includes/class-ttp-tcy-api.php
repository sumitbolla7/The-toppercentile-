<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TTP_TCY_API {

    private $api_url = 'https://www.tcyonline.com/api/erp_request.php';
    private $client_id;
    private $security_code;

    public function __construct() {
        $this->client_id     = function_exists( 'ttp_normalize_tcy_client_id' )
            ? ttp_normalize_tcy_client_id( get_option( 'ttp_tcy_client_id', '' ) )
            : preg_replace( '/\D+/', '', (string) get_option( 'ttp_tcy_client_id', '' ) );
        $this->security_code = get_option( 'ttp_tcy_security_code', '' );
    }

    public function register_student( $data ) {
        $mobile = isset( $data['mobile'] ) ? (string) $data['mobile'] : '';
        if ( function_exists( 'ttp_tcy_normalize_mobile_for_api' ) ) {
            $mobile = ttp_tcy_normalize_mobile_for_api( $mobile );
        }
        $full_name = sanitize_text_field( $data['full_name'] ?? '' );
        if ( function_exists( 'ttp_sanitize_tcy_full_name' ) ) {
            $full_name = ttp_sanitize_tcy_full_name( $full_name );
        } elseif ( function_exists( 'ttp_sanitize_person_name_value' ) ) {
            $full_name = ttp_sanitize_person_name_value( $full_name );
        }
        if ( '' === $full_name || is_email( $full_name ) ) {
            $full_name = 'Student';
        }
        $client_id = function_exists( 'ttp_normalize_tcy_client_id' )
            ? ttp_normalize_tcy_client_id( $this->client_id )
            : preg_replace( '/\D+/', '', (string) $this->client_id );
        $payload = [
            'action'        => 'register',
            'client_id'     => $client_id,
            'security_code' => $this->security_code,
            'full_name'     => $full_name,
            'email'         => sanitize_email( $data['email'] ),
            'mobile_number' => sanitize_text_field( $mobile ),
            'course_id'     => sanitize_text_field( $data['course_id'] ),
            'category_id'   => sanitize_text_field( $data['category_id'] ),
            // TCY Postman / ERP sheet: 0 = tests focus, 1 = online tab (default 0 per client).
            'enable_online_tab' => (int) apply_filters( 'ttp_tcy_register_enable_online_tab', (int) get_option( 'ttp_tcy_enable_online_tab', 0 ) ),
        ];
        $payload = apply_filters( 'ttp_tcy_register_request_body', $payload, $data );
        $response = $this->request( $payload );
        $this->log( 'register', $payload, $response, $data['order_id'] ?? null );
        return $response;
    }

    public function login_student( $tcy_user_id ) {
        $tcy_user_id = sanitize_text_field( (string) $tcy_user_id );
        $payload     = [
            'action'        => 'login',
            'client_id'     => $this->client_id,
            'security_code' => $this->security_code,
            'user_id'       => $tcy_user_id,
        ];
        $payload = apply_filters( 'ttp_tcy_login_request_body', $payload, $tcy_user_id );
        $response = $this->request( $payload );
        $this->log( 'login', $payload, $response );
        return $response;
    }

    public function get_courses() {
        $payload  = [ 'action' => 'get_courses', 'client_id' => $this->client_id, 'security_code' => $this->security_code ];
        $response = $this->request( $payload );
        $this->log( 'get_courses', $payload, $response );
        return $response;
    }

    /**
     * Remove / unassign a course from a TCY student (action name varies by ERP build).
     *
     * @param string $tcy_user_id  TCY user id.
     * @param string $course_id     TCY course id.
     * @param string $category_id   category_id (MBA 100000 or pack id).
     * @param int|null $order_id    Order id for logs.
     * @return array Decoded API response.
     */
    public function remove_course( $tcy_user_id, $course_id, $category_id = '100000', $order_id = null ) {
        $tcy_user_id = function_exists( 'ttp_sanitize_tcy_user_id' )
            ? ttp_sanitize_tcy_user_id( $tcy_user_id )
            : sanitize_text_field( (string) $tcy_user_id );
        $course_id   = sanitize_text_field( (string) $course_id );
        $category_id = sanitize_text_field( (string) $category_id );
        if ( $category_id === '' ) {
            $category_id = '100000';
        }

        $actions = apply_filters(
            'ttp_tcy_remove_course_action_names',
            [ 'remove_course', 'delete_course', 'unassign_course' ]
        );
        if ( function_exists( 'ttp_tcy_is_fast_access_request' ) && ttp_tcy_is_fast_access_request() ) {
            $actions = array_slice( (array) $actions, 0, 1 );
        }

        $last = [ 'success' => 0, 'error' => 'No remove_course action attempted' ];
        foreach ( (array) $actions as $action ) {
            $action = sanitize_key( (string) $action );
            if ( $action === '' ) {
                continue;
            }
            $payload = [
                'action'        => $action,
                'client_id'     => $this->client_id,
                'security_code' => $this->security_code,
                'user_id'       => $tcy_user_id,
                'course_id'     => $course_id,
                'category_id'   => $category_id,
            ];
            $payload  = apply_filters( 'ttp_tcy_remove_course_request_body', $payload, $course_id, $category_id );
            $response = $this->request( $payload );
            $this->log( 'remove_course', $payload, $response, $order_id );
            $last = is_array( $response ) ? $response : $last;
            if ( function_exists( 'ttp_tcy_api_is_success' ) && ttp_tcy_api_is_success( $response ) ) {
                return $response;
            }
        }
        return $last;
    }

    public function add_course( $tcy_user_id, $course_id, $category_id, $order_id = null, $context = [] ) {
        $payload = [
            'action'        => 'add_course',
            'client_id'     => $this->client_id,
            'security_code' => $this->security_code,
            'user_id'       => function_exists( 'ttp_sanitize_tcy_user_id' )
                ? ttp_sanitize_tcy_user_id( $tcy_user_id )
                : sanitize_text_field( (string) $tcy_user_id ),
            'course_id'     => sanitize_text_field( (string) $course_id ),
            'category_id'   => sanitize_text_field( (string) $category_id ),
        ];
        if ( is_array( $context ) && ! empty( $context['sub_cat'] ) ) {
            $payload['sub_cat'] = sanitize_text_field( (string) $context['sub_cat'] );
        }
        $payload  = apply_filters( 'ttp_tcy_add_course_request_body', $payload, $context );
        $response = $this->request( $payload );
        $this->log( 'add_course', $payload, $response, $order_id );
        return $response;
    }

    public function test_connection() {
        $cid = get_option( 'ttp_tcy_client_id', '' );
        $sec = get_option( 'ttp_tcy_security_code', '' );
        if ( empty( $cid ) ) return [ 'success' => 0, 'error' => 'Client ID is empty. Save settings first.' ];
        if ( empty( $sec ) ) return [ 'success' => 0, 'error' => 'Security Code is empty. Save settings first.' ];
        $this->client_id     = $cid;
        $this->security_code = $sec;
        return $this->request( [ 'action' => 'get_courses', 'client_id' => $cid, 'security_code' => $sec ] );
    }

    private function request( $payload ) {
        if ( isset( $payload['client_id'] ) ) {
            $payload['client_id'] = function_exists( 'ttp_normalize_tcy_client_id' )
                ? ttp_normalize_tcy_client_id( $payload['client_id'] )
                : preg_replace( '/\D+/', '', (string) $payload['client_id'] );
        }
        $resp = wp_remote_post( $this->api_url, [
            'method'    => 'POST',
            'timeout'   => (int) apply_filters( 'ttp_tcy_api_http_timeout', 18 ),
            'sslverify' => false,
            'headers'   => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
            'body'      => http_build_query( $payload ),
        ] );

        if ( is_wp_error( $resp ) ) {
            return [ 'success' => 0, 'error' => 'HTTP Error: ' . $resp->get_error_message() ];
        }

        $body    = wp_remote_retrieve_body( $resp );
        $decoded = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [ 'success' => 0, 'error' => 'Invalid JSON from TCY. HTTP ' . wp_remote_retrieve_response_code( $resp ), 'raw' => substr( $body, 0, 300 ) ];
        }
        return $decoded;
    }

    private function log( $action, $req, $resp, $order_id = null ) {
        global $wpdb;
        $log = $req;
        unset( $log['security_code'] );
        $table = $wpdb->prefix . 'ttp_api_logs';
        if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) return;
        $status = 'failed';
        if ( function_exists( 'ttp_tcy_api_is_success' ) && ttp_tcy_api_is_success( $resp ) ) {
            $status = 'success';
        } elseif ( 'add_course' === $action && function_exists( 'ttp_tcy_add_course_outcome' ) ) {
            $outcome = ttp_tcy_add_course_outcome( $resp );
            if ( in_array( $outcome, [ 'already', 'pack_conflict' ], true ) ) {
                $status = 'success';
            }
        }
        $wpdb->insert( $table, [
            'order_id'      => $order_id,
            'action'        => $action,
            'request_data'  => json_encode( $log ),
            'response_data' => json_encode( $resp ),
            'status'        => $status,
        ] );
    }
}
