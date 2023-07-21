<?php
/**
 * Handles the API calls
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/includes
 * @since 1.1.0
 */
class CRMFWC_Call {

	/**
	 * The base part for composing the endpoints
	 *
	 * @var string
	 */
	private $base_url = 'https://app.crmincloud.it/api/v1/';

	/**
	 * The CRM in Cloud user email
	 *
	 * @var string
	 */
	private $email;

	/**
	 * The CRM in Cloud user password
	 *
	 * @var string
	 */
	private $passw;


	/**
	 * The constructor
	 */
	public function __construct() {

		$this->email = get_option( 'crmfwc-email' );
		$this->passw = get_option( 'crmfwc-passw' );

	}


	/**
	 * Get the access token
	 *
	 * @param string $email the CRM in Cloud email.
	 * @param string $passw the CRM in Cloud password.
	 * @return string
	 */
	public function get_access_token( $email = null, $passw = null ) {

        $access_token = get_transient( 'crmfwc-access-token');
        $time_limit   = get_option( '_transient_timeout_crmfwc-access-token' );
        /* error_log( 'TIME LIMIT: ' . wp_date( 'd-m-Y H:i:s', $time_limit ) ); */

        if ( $access_token ) {

            /* error_log( 'TRANSIENT: ' . $access_token ); */
            return $access_token;

        } else {

            $email  = $email ? $email : $this->email;
            $passw  = $passw ? $passw : $this->passw;

            if ( $email && $passw ) {

                $data  = array(
                    'grant_type' => 'password',
                    'username'   => $email,
                    'password'   => $passw,
                );

                error_log( 'ARRAY TOKEN: ' . print_r( $data, true ) );

                $response = $this->call( 'post', 'Auth/Login', $data, true );
                error_log( 'RESPONSE TOKEN: ' . print_r( $response, true ) );

                if ( isset( $response->access_token ) ) {

                    set_transient( 'crmfwc-access-token', $response->access_token, 1200 );

                    return $response->access_token;

                } elseif ( isset( $response->error ) ) {

                    return $response;

                }

            }

        }

	}


	/**
	 * Define the headers to use in every API call
	 *
	 * @param bool  $login  token not required in case of login.
	 * @param bool  $upload change headers in case of upload.
     * @param string $boundary the boundary.
     *
	 * @return array
	 */
	public function headers( $login = false, $upload = false, $boundary = null ) {

        if ( $upload ) {

            $output = array(
                'content-type' => 'multipart/form-data; boundary=' . $boundary,
            );

        } else {

            $output = array(
                'Content-Type'  => 'application/json',
            );

        }

		if ( ! $login ) {

			$output['Authorization'] = 'Bearer ' . $this->get_access_token();
		}

		return $output;

	}


	/**
	 * The call
	 *
	 * @param string $method   could be GET, POST, DELETE or PUT.
	 * @param string $endpoint the endpoint's name.
	 * @param array  $args     the data.
	 * @param bool   $login    token not required in case of login.
	 * @param bool   $upload   change headers in case of upload.
     * @param string $boundary the boundary.
     *
	 * @return mixed  the response
	 */
	public function call( $method, $endpoint = '', $args = null, $login = false, $upload = false, $boundary = null ) {

        /* error_log( 'ARGS: ' . print_r( $args, true ) ); */

		$body = ( $args && ! $upload ) ? json_encode( $args ) : $args;

        $headers = $this->headers( $login, $upload, $boundary );

		$response = wp_remote_request(
			$this->base_url . $endpoint,
			array(
				'method'  => $method,
				'headers' => $headers,
				'timeout' => 20,
				'body'    => $body,
			)
		);

        if ( 'Catalog' === $endpoint ) {

            error_log( 'HEADERS: ' . print_r( $headers, true ) );
            error_log( 'RESPONSE: ' . print_r( $response['response'], true ) );

        }

		if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {

			$output = json_decode( $response['body'] );

			if ( isset( $output->error ) || isset( $output->message ) ) {

				error_log( 'CRMFWC | ERROR: ' . print_r( $output, true ) );

			}

			return $output;

		} else {

			/*Print the error to the log*/
			error_log( 'CRMFWC | WP ERROR: ' . print_r( $response, true ) );

		}

	}

}
