<?php
/**
 * Main RCP GetResponse class
 *
 * @package svbk-rcp-getresponse
 * @author Brando Meniconi <b.meniconi@silverbackstudio.it>
 */

namespace Svbk\WP\Plugins\RCP\GetResponse;

use Svbk\WP\Helpers;


/**
 * Main RCP GetResponse class
 */
class Integration {

	/**
	 * Prints the HTML fields in subscrioption's admin panel
	 *
	 * @var GetResponse $client The GetResponse API client instance.
	 */
	protected $client;

	/**
	 * Constructor, instantiate the GR API
	 *
	 * @param object $apikey The GetResponse API key.
	 *
	 * @return void
	 */
	public function __construct( $apikey ) {

		$this->client = new Helpers\Mailing\GetResponse( $apikey );

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
		
		$campaigns = $this->client->getCampaigns();
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="getresponse_campaign_id"><?php esc_html_e( 'GetResponse Campaign', 'svbk-rcp-getresponse' ); ?></label>
			</th>
			<td>
			<?php if ( $campaigns ) :
				$campaigns = wp_list_pluck( $campaigns, 'name', 'campaignId' );			?>
				<select name="getresponse_campaign_id" id="getresponse_campaign_id">
						<option value="" <?php selected( $defaults['getresponse_campaign_id'], '' )?> ><?php esc_html_e( '- Select - ', 'svbk-rcp-getresponse' ) ?></option>
					<?php foreach ( $campaigns as $campaign_id => $campaign_name ) : ?>
						<option value="<?php echo esc_attr( $campaign_id ); ?>" <?php selected( $defaults['getresponse_campaign_id'], $campaign_id )?> ><?php echo esc_html( $campaign_name ); ?></option>
					<?php endforeach; ?>
				</select>
			
				<p class="description"><?php esc_html_e( 'The campaign the user should be subscribet to.', 'svbk-rcp-getresponse' ); ?></p>
			<?php else: ?>
				<p ><?php esc_html_e( 'No available GetResponse campaigns. Please create one.', 'svbk-rcp-getresponse' ); ?></p>
			<?php endif; ?>
			</td>
		</tr>
	<?php
	}


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
	 * Set campaign accordingly
	 *
	 * @param int        $subscription_id The subscription id.
	 * @param int        $user_id The user id .
	 * @param RCP_Member $member The RCP_Member object.
	 *
	 * @return void
	 */
	public function update( $subscription_id, $user_id, $member ) {

		global $rcp_levels_db;

		$campaign_id = $rcp_levels_db->get_meta( $subscription_id, 'getresponse_campaign_id', true );

		if ( ! $campaign_id ) {
			return;
		}

		$args = array(
			'name' => $member->first_name . ' ' . $member->last_name,
		);

		return $this->client->subscribe( $campaign_id, $member->user_email, $args, true );

	}


}
