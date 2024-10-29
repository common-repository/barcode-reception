<?php
/*
 Plugin Name: Barcode Reception
Plugin URI: http://residentbird.main.jp/bizplugin/plugins/barcode-reception/
Description: WordPressをバーコード受付システムにするプラグインです
Version: 0.4.0
Author:WordPress Biz Plugin
Author URI: http://residentbird.main.jp/bizplugin/
*/


set_include_path( dirname(__FILE__).'/lib');
include_once( dirname(__FILE__).'/lib/Image/Barcode2.php' );
include_once( dirname(__FILE__).'/admin-ui.php' );

new BarcodeReception();

class BCR {
	const VERSION = "0.3.0";
	const SHORTCODE_BARCODE = "showbarcode";
	const SHORTCODE_RECEPTION = "showreception";
	const OPTIONS = "barcode_options";
	const META_KEY = 'entry_date';
	const OFFSET = 140120;
	const MENU_PAGE = 'barcode_menu.php';
	const SUBMENU_USERS_PAGE = 'barcode_users.php';

	public function barcode2userid($code){
		$option = self::get_option();
		$offset = $option['bcr_offset'];
		return $code - $offset;
	}

	public function userid2code($userid){
		$option = self::get_option();
		$usercode = sprintf("%08d", $userid + $option['bcr_offset'] );
		return $usercode;
	}

	public static function get_option(){
		return get_option(self::OPTIONS);
	}

	public static function delete_option(){
		$option = get_option(self::OPTIONS);
		if ($option){
			delete_option(self::OPTIONS);
		}
	}

	public static function update_option( $options ){
		if ( empty($options)){
			return;
		}
		update_option(self::OPTIONS, $options);
	}

	public static function enqueue_css_js(){
		wp_enqueue_style('jquery-ui-theme',  plugins_url('/lib/blitzer/jquery-ui-1.10.3.custom.min.css', __FILE__ ));
		wp_enqueue_style('barcode-style',  plugins_url('/css/barcode-reception.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'reception-js', plugins_url('reception.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	public static function enqueue_admin_css_js(){
		/*
		 wp_enqueue_style( 'whats-new-style', plugins_url('whats-new.css', __FILE__ ) );
		wp_enqueue_script( 'whats-new-admin-js', plugins_url('whats-new-admin.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
		*/
	}

	public static function localize_js(){
		wp_localize_script( 'reception-js', 'BCR_Setting', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'action' => 'ajax_request'
		));
	}
}


/**
 * プラグイン本体
 */
class BarcodeReception{

	var $adminUi;
	var $reception = null;

	public function __construct(){
		register_activation_hook(__FILE__, array(&$this,'on_activation'));
		register_deactivation_hook(__FILE__, array(&$this,'on_deactivation'));
		add_action( 'admin_init', array(&$this,'on_admin_init') );
		add_action( 'admin_menu', array(&$this, 'on_admin_menu'));
		add_action( 'wp_enqueue_scripts', array(&$this,'on_enqueue_scripts'));
		add_action( 'wp_ajax_ajax_request', array(&$this,'ajax_request') );
		add_action( 'wp_ajax_nopriv_ajax_request', array(&$this,'ajax_request') );
		add_action( 'edit_user_profile', array(&$this, 'on_edit_user_profile' ) );
		add_action( 'show_user_profile', array(&$this, 'on_edit_user_profile' ) );
		add_shortcode( BCR::SHORTCODE_BARCODE, array(&$this,'show_user_barcode'));
		add_shortcode( BCR::SHORTCODE_RECEPTION, array(&$this,'show_reception'));
		add_filter( 'widget_text', 'do_shortcode');
		$this->reception = new Reception();
	}

	function on_activation() {
		$option = BCR::get_option();
		if($option) {
			return;
		}
		$arr = array(
				"bcr_offset" => "98765",
				"bcr_clear" => false
		);
		BCR::update_option( $arr );
	}

	function on_deactivation() {
		BCR::delete_option();
	}

	function on_admin_init() {
		// 		BCR::enqueue_admin_css_js();
		$this->adminUi = new BCRAdminUi(__FILE__);
	}

	public function on_admin_menu() {
		add_menu_page("Barcode 設定", "Barcode 設定", 'administrator', BCR::MENU_PAGE, array(&$this->adminUi, 'show_admin_page'));
		add_submenu_page(BCR::MENU_PAGE, "受付済みユーザ一覧", "受付済みユーザ一覧", 'administrator', BCR::SUBMENU_USERS_PAGE, array(&$this, 'show_users_page'));
	}

	function on_enqueue_scripts() {
		if ( is_admin() ){
			return;
		}
		BCR::enqueue_css_js();
		BCR::localize_js();
	}

	function on_edit_user_profile( $user ) {
		$entry_logs = implode("<br>",array_reverse( get_user_meta( $user->ID , BCR::META_KEY )));
		?>
			<table class="form-table">
				<tr>
					<th>受付履歴</th>
					<td>
						<p><?php echo $entry_logs; ?></p>
					</td>
				</tr>
			</table>
		<?php
	}

	/**
	 * Ajax
	 */
	function ajax_request(){
		$request = $_REQUEST['request'];
		if ( empty( $request ) || !method_exists( $this, $request ) || !current_user_can('edit_users') ){
			die();
		}
		$this->$request();
	}

	private function send_message($result, $msg){
		$array = array( 'result' => $result, 'message' => $msg);
		$this->response_json( $array );
	}

	private function search_user(){
		$barcode = absint( $_REQUEST['barcode'] );
		if ( $barcode == 0){
			$this->send_message("NG", "不正なデータです");
			die();
		}
		$wp_user = $this->reception->search_user($barcode);
		if ( empty($wp_user)){
			$this->send_message("NG", "登録されていません");
			die();
		}
		$user = array(
				'login_name' => $wp_user->user_login,
				'full_name' => $wp_user->last_name . " " . $wp_user->first_name,
				'email' => $wp_user->user_email,
		);

		$nonce = wp_create_nonce( 'entry_user'.$barcode );
		$array = array( 'user' => $user, 'nonce' => $nonce );
		$this->response_json( $array );
		die();
	}

	private function entry_user(){
		$nonce = $_REQUEST['nonce'];
		$barcode = absint( $_REQUEST['barcode'] );
		if ( ! wp_verify_nonce( $nonce, 'entry_user'.$barcode ) ) {
			$this->send_message("NG", "不正なデータです");
			die();
		}
		try{
			$this->reception->entry_user($barcode);
		}catch (Exception $e) {
			$this->send_message("NG", $e->getMessage());
			die();
		}
		$this->send_message("OK", "登録されました");
		die();
	}

	private function response_json($ary){
		$charset = get_bloginfo( 'charset' );
		$json = json_encode( $ary );
		nocache_headers();
		header( "Content-Type: application/json; charset=$charset" );
		echo $json;
	}

	/**
	 * ログインユーザのバーコードを表示する
	 * @return string
	 */
	function show_user_barcode(){
		$userid = get_current_user_id();
		if ( $userid == 0){
			return "ログインしていません。";
		}
		ob_start();
		$ub = new UserBarcode($userid);
		include( dirname(__FILE__).'/barcode-view.php' );
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * 受付画面を表示する
	 * @return string
	 */
	function show_reception(){
		if ( !current_user_can('edit_users') ){
			return;
		}
		ob_start();
		include( dirname(__FILE__).'/reception-view.php' );
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * 受付済みユーザ一覧ページ
	 */
	public function show_users_page() {
		$users = $this->reception->get_entry_users();
		include( dirname(__FILE__).'/entry-users-view.php' );
	}
}

class Reception{
	public function __construct(){
	}

	/**
	 * バーコードの値からユーザを検索する
	 * @param unknown_type $barcode
	 */
	public function search_user( $barcode ){
		$userid = BCR::barcode2userid($barcode);
		return get_userdata( $userid );
	}

	public function entry_user( $barcode ){
		$userid = BCR::barcode2userid($barcode);
		if ( $userid == null ){
			throw new Exception('登録されていないユーザです');
		}
		$this->add_entry_data($userid);
	}

	public function get_entry_users(){
		$search_word = date_i18n("Y-m-d ");
		$args = array(
				'meta_query' => array(
						'relation' => 'OR',
						array(
								'key' => BCR::META_KEY,
								'value' => $search_word,
								'compare' => 'LIKE'
						)
				)
		);
		$users = get_users($args);
		foreach ( $users as $user ) {
			$entry_logs = get_user_meta( $user->ID , BCR::META_KEY );
			$user->dateTime = end($entry_logs);
		}
		return $users;
	}

	private function add_entry_data($userid){
		$entry_logs = get_user_meta( $userid , BCR::META_KEY );
		if ( $this->isToday( end($entry_logs) )){
			throw new Exception('すでに登録されています');
		}
		$mata_value = date_i18n("Y-m-d H:i:s");
		add_user_meta( $userid, BCR::META_KEY, $mata_value );
		if ( count($entry_logs) > 10 ){
			delete_user_meta( $userid, BCR::META_KEY, $entry_logs[0] );
		}
	}

	private function isToday($date){
		$today = date_i18n("Y-m-d ");
		return strstr( $date, $today);
	}

}

class UserBarcode{
	public $img_url;
	private $image;

	public function __construct($userid){
		$barcode = new Image_Barcode2();
		$usercode = BCR::userid2code($userid);
		$this->image = $barcode->draw($usercode, 'code128', 'png', false);
		$this->img_url = $this->save_image_file();
	}

	private function save_image_file(){
		$filename = 'userbarcode.png';
		$upload_array = wp_upload_dir();
		$upload_dir = $upload_array["basedir"]. "/bcr/";
		$upload_url = $upload_array["baseurl"]. "/bcr/". $filename;
		if (!file_exists($upload_dir)){
			mkdir($upload_dir);
		}
		imagepng($this->image, $upload_dir.$filename);
		return $upload_url;
	}
}
