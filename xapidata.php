<?php

namespace H5PXAPIKATCHU;

/**
 * Pseudo xAPI
 * The goal of this class is not to comply to the xAPI specification, but to
 * create output that's useful for the user.
 *
 * https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md
 *
 * @package H5PXAPIKATCHU
 * @since 0.1
 */
class XAPIDATA {

  private $raw;
  private $data;

  /**
   * Constructor
   */
  public function __construct( $xapi ) {
    // Change from JavaScript
    $xapi = str_replace('\"', '"', $xapi);
    $this->raw = $xapi;

  	$this->data = json_decode($xapi, true);
  }

  /**
   * Get raw xAPI data.
   */
  public function get_raw_xapi() {
    return $this->$raw;
  }

  /**
   * Get actor data from xAPI statement.
   */
  public function get_actor() {
    if ( is_array( $this->data ) && array_key_exists( 'actor', $this->data ) ) {
      $actor = $this->data['actor'];

      $object_type = ( array_key_exists( 'objectType', $actor ) ) ? $actor['objectType'] : '';
      $inverse_functional_identifier = $this->flatten_inverse_functional_identifier ( $actor );
      $name = ( array_key_exists( 'name', $actor )) ? $actor['name'] : '';
      $members = ( array_key_exists( 'member', $actor) ) ? $this->flatten_members( $actor['member'] ) : '';

      // Identified Group or Anonymous Group (we don't need to distinguish here)
      if ( $object_type === 'Group' ) {
        $name = ($name !== '') ? $name . ' (' . __( 'Group' , 'H5PXAPIKATCHU' ) . ')' : $name;
      }

      //Agent
      if ( $object_type === 'Agent' || $object_type === '') {
        // Not really neccessary, but according to xAPI specs agents have no member data
        $members = '';
      }
    }

    return array(
      'inverse_functional_identifier' => isset( $inverse_functional_identifier ) ? $inverse_functional_identifier : '',
      'name' => isset( $name ) ? $name : '',
      'members' => isset( $members ) ? $members : ''
    );
  }

  /**
   * Get verb data from xAPI statement.
   */
  public function get_verb() {
    if ( is_array( $this->data ) && array_key_exists( 'verb', $this->data ) ) {
      $verb = $this->data['verb'];

      $id = array_key_exists( 'id', $verb ) ? $verb['id'] : '';
      $display = array_key_exists( 'display', $verb ) ? $this-> get_locale_string( $verb['display'] ) : '';
    }

    return array(
      'id' => (isset( $id ) ) ? $id : '',
      'display' => ( isset( $display ) ) ? $display : ''
    );
  }

  public function get_object() {
    if ( is_array( $this->data ) && array_key_exists( 'object', $this->data ) ) {
      $object = $this->data['object'];

      $id = array_key_exists( 'id', $object ) ? $object['id'] : '';
      $definition = array_key_exists( 'definition', $object ) ? $this->get_definition( $object['definition'] ) : '';
      if ( $definition !== '' ) {
        $name = $definition['name'];
        $description = $definition['description'];
        $choices = $definition['choices'];
        $correct_responses_pattern = $definition['correctResponsesPattern'];
      }
    }

    return array(
      'id' => isset( $id ) ? $id : '',
      'name' => isset( $name ) ? $name : '',
      'description' => isset( $description ) ? $description : '',
      'choices' => isset( $choices ) ? $choices : '',
      'correctResponsesPattern' => isset( $correct_responses_pattern ) ? $correct_responses_pattern : ''
    );

  }

  public function get_result() {
    if ( is_array( $this->data ) && array_key_exists( 'result', $this->data ) ) {
      $result = $this->data['result'];

      $response = array_key_exists( 'response', $result ) ? $result['response'] : '';
      $scores = array_key_exists( 'score', $result ) ? $this->get_scores( $result['score'] ) : '';
      if ( $scores !== '' ) {
        $score_raw = $scores['score_raw'];
        $score_scaled = $scores['score_scaled'];
      }
      $completed =  array_key_exists( 'completed', $result ) ? $result['completed'] : '';
      $success =  array_key_exists( 'success', $result ) ? $result['success'] : '';
      $duration =  array_key_exists( 'duration', $result ) ? $result['duration'] : '';
    }

    return array(
      'response' => isset( $response) ? $response : '',
      'score_raw' => isset( $score_raw ) ? $score_raw : '',
      'score_scaled' => isset( $score_scaled ) ? $score_scaled : '',
      'completed' => isset( $completed ) ? $completed : '',
      'success' => isset( $success ) ? $success : '',
      'duration' => isset( $duration ) ? $duration: ''
    );
  }

  /**
   * Flatten xAPI member object.
   */
  private function flatten_members ( $members ) {
    if ( ! is_array( $members ) || empty( $members ) ) {
      return '';
    }

    $output = array();
    foreach ( $members as $member ) {
      array_push( $output, $this->flatten_agent( $member ) );
    }
    return implode( $output, ', ' );
  }

  /**
   * Flatten xAPI agent object.
   */
  private function flatten_agent ( $agent ) {
    if ( ! is_array( $agent ) || empty( $agent ) ) {
      return '';
    }

    $name = ( array_key_exists( 'name', $agent ) ) ? $agent['name'] : '';
    $ifi = $this->flatten_inverse_functional_identifier( $agent );

    if ( $name !== '' && $ifi !== '' ) {
      $name = ' (' . $name . ')';
    }

    return $ifi . $name ;
  }

  /**
   * Flatten xAPI InverseFunctionalIdentifier object.
   */
  private function flatten_inverse_functional_identifier ( $actor ) {
    if ( ! is_array( $actor ) || empty( $actor ) ) {
      return '';
    }

    $inverse_functional_identifier = array();
    if ( array_key_exists( 'mbox', $actor ) ) {
      array_push( $inverse_functional_identifier, __( 'email', 'H5PXAPIKATCHU' ) . ': ' . $actor['mbox'] );
    }
    if ( array_key_exists( 'mbox_sha1sum', $actor ) ) {
      array_push( $inverse_functional_identifier, __( 'email hash', 'H5PXAPIKATCHU' ) . ': ' . $actor['mbox_sha1sum'] );
    }
    if ( array_key_exists( 'openid', $actor ) ) {
      array_push( $inverse_functional_identifier, __( 'openid', 'H5PXAPIKATCHU' ) . ': ' . $actor['openid'] );
    }
    if ( array_key_exists( 'account', $actor ) ) {
      array_push( $inverse_functional_identifier, __( 'account', 'H5PXAPIKATCHU' ) . ': ' . $this->flatten_account( $actor['account'] ) );
    }
    return ( empty( $inverse_functional_identifier ) ) ? '' : implode( $inverse_functional_identifier, ', ' );
  }

  /**
   * Flatten xAPI account object.
   */
  private function flatten_account ( $account ) {
    if ( ! is_array( $account ) || empty( $account ) ) {
      return '';
    }

    $name = ( array_key_exists( 'name', $account ) ) ? $account['name'] : '';
    $homepage = ( array_key_exists( 'homePage', $account ) ) ? $account['homePage'] : '';

    if ( $name !== '' && $homepage !== '' ) {
      $homepage = ' (' . $homepage . ')';
    }

    return $name . $homepage;
  }

  /**
   * Get local string from xAPI language map object
   */
  private function get_locale_string ( $language_map ) {
    if ( ! is_array( $language_map ) || empty( $language_map ) ) {
      return '';
    }

    $LOCALE_DEFAULT = 'en-US';
    $locale = str_replace( '_', '-', get_locale() );

    if ( array_key_exists( $locale, $language_map ) ) {
      return $language_map[$locale];
    }
    if ( array_key_exists( $locale, $language_map ) ) {
      return $language_map[$LOCALE_DEFAULT];
    }
    return array_values($language_map)[0];
  }

  /**
   * Get xAPI description object
   */
  private function get_definition( $definition ) {
    if (is_array( $definition ) ) {
      $name = array_key_exists( 'name', $definition ) ? $this->get_locale_string( $definition['name'] ) : '';
      $description = array_key_exists( 'description', $definition ) ? $this->get_locale_string( $definition['description'] ) : '';
      $choices = array_key_exists( 'choices', $definition ) ? $this->flatten_choices( $definition['choices'] ) : '';
      $correct_responses_pattern = array_key_exists( 'correctResponsesPattern', $definition ) ? $this->flatten_correct_responses_pattern( $definition['correctResponsesPattern'] ) : '';
    }

    return array(
      'name' => isset( $name ) ? $name : '',
      'description' => isset( $description ) ? $description : '',
      'choices' => isset( $choices ) ? $choices : '',
      'correctResponsesPattern' => isset( $correct_responses_pattern ) ? $correct_responses_pattern : ''
    );
  }

  /**
   * Flatten xAPI choices object.
   */
  private function flatten_choices ( $choices ) {
    if ( ! is_array( $choices ) || empty ( $choices ) ) {
      return '';
    }

    $output = array();
    foreach( $choices as $choice ) {
      $id = array_key_exists( 'id', $choice ) ? $choice['id'] : '';
      $description = array_key_exists( 'description', $choice ) ? $this->get_locale_string( $choice['description'] ) : '';

      array_push( $output, '[' . $id . '] ' . $description );
    }

    return implode( $output, ', ' );
  }

  /**
   * Flatten xAPI correctResponsesPattern object.
   */
  private function flatten_correct_responses_pattern ( $correct_responses_patterns ) {
    if ( ! is_array( $correct_responses_patterns ) || empty ( $correct_responses_patterns ) ) {
      return '';
    }

    $output = array();
    foreach( $correct_responses_patterns as $key => $pattern ) {
      array_push( $output, '[' . $key . ']: ' . $pattern );
    }

    return implode( $output, ', ' );

  }

  /**
   * Get score details from xAPI score object.
   */
  private function get_scores ( $scores ) {
    if ( is_array( $scores ) ) {
      $score_raw = array_key_exists( 'raw', $scores ) ? $scores['raw'] : '';
      $score_scaled = array_key_exists( 'scaled', $scores ) ? $scores['scaled'] : '';
    }

    return array(
      'score_raw' => isset( $score_raw ) ? $score_raw : '',
      'score_scaled' => isset( $score_scaled ) ? $score_scaled : ''
    );
  }
}