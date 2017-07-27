<?php
/**
 * Main RCP GetResponse class
 *
 * @package svbk-rcp-getresponse
 * @author Brando Meniconi <b.meniconi@silverbackstudio.it>
 */

namespace Svbk\WP\Plugins\RCP\GetResponse;

use GetResponse;


/**
 * Main RCP GetResponse class
 */
class Integration {

    protected $client;
    
    public function __construct( $apikey ){
        
        $this->client = new GetResponse( $apikey );
        
    }

	/**
	 * Prints the HTML fields in subscrioption's admin panel
	 *
	 * @param object $level Optional. The subscription level object.
	 *
	 * @return void
	 */
	public function admin_subscirption_form( $level = null ) {
		global $rcp_levels_db;

		$defaults = array(
			'getresponse_campaign_id' => '',
		);

		if ( ! empty( $level ) ) {
			$defaults['getresponse_campaign_id'] = $rcp_levels_db->get_meta( $level->id, 'getresponse_campaign_id', true );
		}
		?>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="getresponse_campaign_id"><?php esc_html_e( 'GetResponse Campaign', 'svbk-rcp-getresponse' ); ?></label>
			</th>
			<td>
				<select name="getresponse_campaign_id" id="getresponse_campaign_id">
				        <option value="" <?php selected( $defaults['getresponse_campaign_id'], '' )?> ><?php esc_html_e('- Select - ', 'svbk-rcp-getresponse') ?></option>
				<?php
					$campaigns = $this->get_campaigns();

				    foreach ( $campaigns as $campaign_id => $campaign_name ) : ?>
    					<option value="<?php echo esc_attr( $campaign_id ); ?>" <?php selected( $defaults['getresponse_campaign_id'], $campaign_id )?> ><?php echo esc_html( $campaign_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'The the campaign the user should be subscribet to.', 'svbk-rcp-getresponse' ); ?></p>
			</td>
		</tr>

	<?php }


	/**
	 * Saves values from the subscription admin panel.
	 *
	 * @param int   $level_id The subscription level ID.
	 * @param array $args The submitted form filed values.
	 *
	 * @return void
	 */
	public function admin_subscirption_form_save( $level_id, $args ) {

		global $rcp_levels_db;

		$defaults = array(
		    'getresponse_campaign_id' => '',
		);

		$args = wp_parse_args( $args, $defaults );
		
		if ( current_filter() === 'rcp_add_subscription' ) {
			$rcp_levels_db->add_meta( $level_id, 'getresponse_campaign_id', sanitize_text_field( $args['getresponse_campaign_id'] ) );
		} elseif ( current_filter() === 'rcp_pre_edit_subscription_level' ) {
			$rcp_levels_db->update_meta( $level_id, 'getresponse_campaign_id', sanitize_text_field( $args['getresponse_campaign_id'] ) );
		}
	}

    /**
     * Get available campaigns via GetResponse API
     *
     * @return void
     */
    public function get_campaigns(){
        
        $this->client->getCampaigns();
        
        $campaigns = get_transient( 'svbk_rcp_getresponse_campaigns' );
        
        if ( false === $campaigns ) {
             $campaigns = $this->client->getCampaigns();
             set_transient( 'svbk_rcp_getresponse_campaigns', $campaigns, 10 * MINUTE_IN_SECONDS );
        }        
    
        if ( (200 !== $this->client->http_status) || empty( $campaigns ) ) {
            return array();    
        }
        
        return wp_list_pluck($campaigns, 'name', 'campaignId');
        
    }
    
    /**
     * Set campaign accordingly
     *
     * @return void
     */
    public function update( $subscription_id, $user_id, $member ) {
    	
		global $rcp_levels_db;

		$campaign_id = $rcp_levels_db->get_meta( $subscription_id, 'getresponse_campaign_id', true );
        
        if ( ! $campaign_id ) {
            return;
        }
    
    	$getUser = (array) $this->client->getContacts(array(
    		'query' => array(
    			'email' => $member->user_email,
    		),
    		'fields' => 'contactId'
    	));
    
    	if ( (200 === $this->client->http_status) && ! empty( $getUser ) && isset( $getUser[0] ) ) {
    		
    		$contactId = $getUser[0]->contactId;
    		
    		$updateResult = $this->client->updateContact( 
    			$contactId,
    			array(
    			    'campaign' => array(
    			        'campaignId' => $campaign_id,
    				)
    			)
    		);		
    		
    	} else {
    	    
    		$addResult = $this->client->addContact(
    			array(
    			    'name'              => $member->first_name . ' ' . $member->last_name ,
    			    'email'             => $member->user_email,
    			    'dayOfCycle'        => 0,
    			    'ipAddress'         => $_SERVER['REMOTE_ADDR'],
    			    'campaign' => array(
    			        'campaignId' => $campaign_id,
    				)
    			)
    		);
    		
    		
    	}
    	
    }

    
}
