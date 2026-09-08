<?php

if (!defined('ABSPATH')) {
	exit;
}

class Twoo_GitHub_Updater
{
	private $file;
	private $basename;
	private $slug;
	private $owner;
	private $repo;
	private $asset;

	public function __construct($file, $owner, $repo, $asset = '')
	{
		$this->file     = $file;
		$this->basename = plugin_basename($file);
		$this->slug     = dirname($this->basename);
		$this->owner    = $owner;
		$this->repo     = $repo;
		$this->asset    = $asset;

		add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
		add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
		add_filter('upgrader_source_selection', array($this, 'fix_source'), 10, 4);
		add_action('upgrader_process_complete', array($this, 'flush_cache'));
	}

	private function cache_key()
	{
		return 'twoo_updater_' . md5($this->owner . '/' . $this->repo);
	}

	public function flush_cache()
	{
		delete_transient($this->cache_key());
	}

	private function current_version()
	{
		$data = get_file_data($this->file, array('Version' => 'Version'));

		return !empty($data['Version']) ? $data['Version'] : '0';
	}

	private function get_release()
	{
		$key = $this->cache_key();

		if (empty($_GET['force-check'])) {
			$cached = get_transient($key);
			if ($cached !== false) {
				return is_array($cached) ? $cached : false;
			}
		}

		$ttl = apply_filters('twoo_updater_cache_seconds', 12 * HOUR_IN_SECONDS);
		$url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->owner, $this->repo);

		$response = wp_remote_get($url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'twoo-performant-uprise-updater',
			),
		));

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			set_transient($key, 'none', $ttl);

			return false;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		if (empty($body['tag_name'])) {
			set_transient($key, 'none', $ttl);

			return false;
		}

		$package = '';
		if (!empty($body['assets']) && is_array($body['assets'])) {
			foreach ($body['assets'] as $a) {
				if ($this->asset !== '' && isset($a['name']) && $a['name'] === $this->asset) {
					$package = $a['browser_download_url'];
					break;
				}
				if ($this->asset === '' && isset($a['name']) && substr($a['name'], -4) === '.zip') {
					$package = $a['browser_download_url'];
					break;
				}
			}
		}
		if ($package === '' && !empty($body['zipball_url'])) {
			$package = $body['zipball_url'];
		}

		$release = array(
			'version'   => ltrim($body['tag_name'], 'vV'),
			'package'   => $package,
			'url'       => isset($body['html_url']) ? $body['html_url'] : '',
			'changelog' => isset($body['body']) ? $body['body'] : '',
			'published' => isset($body['published_at']) ? $body['published_at'] : '',
		);

		set_transient($key, $release, $ttl);

		return $release;
	}

	public function check_update($transient)
	{
		if (!is_object($transient) || empty($transient->checked)) {
			return $transient;
		}

		$release = $this->get_release();
		if (!$release || empty($release['package'])) {
			return $transient;
		}

		$current = $this->current_version();
		$item    = array(
			'slug'         => $this->slug,
			'plugin'       => $this->basename,
			'new_version'  => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
		);

		if (version_compare($release['version'], $current, '>')) {
			$transient->response[$this->basename] = (object) $item;
		} else {
			$item['new_version'] = $current;
			$transient->no_update[$this->basename] = (object) $item;
		}

		return $transient;
	}

	public function plugin_info($result, $action, $args)
	{
		if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->slug) {
			return $result;
		}

		$release = $this->get_release();
		if (!$release) {
			return $result;
		}

		$data = get_file_data($this->file, array(
			'Name'        => 'Plugin Name',
			'Author'      => 'Author',
			'Description' => 'Description',
			'RequiresWP'  => 'Requires at least',
			'RequiresPHP' => 'Requires PHP',
		));

		$info                = new stdClass();
		$info->name          = $data['Name'];
		$info->slug          = $this->slug;
		$info->version       = $release['version'];
		$info->author        = $data['Author'];
		$info->homepage      = $release['url'];
		$info->requires      = $data['RequiresWP'];
		$info->requires_php  = $data['RequiresPHP'];
		$info->download_link = $release['package'];
		$info->trunk         = $release['package'];
		$info->last_updated  = $release['published'];
		$info->sections      = array(
			'description' => wpautop($data['Description']),
			'changelog'   => $release['changelog'] !== ''
				? wpautop(esc_html($release['changelog']))
				: __('See the GitHub releases page for details.', 'twoo-performant-uprise'),
		);

		return $info;
	}

	public function fix_source($source, $remote_source, $upgrader, $hook_extra = array())
	{
		if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) {
			return $source;
		}

		global $wp_filesystem;

		$desired = trailingslashit($remote_source) . $this->slug;
		if (untrailingslashit($source) === $desired) {
			return $source;
		}

		if ($wp_filesystem && $wp_filesystem->move(untrailingslashit($source), $desired)) {
			return trailingslashit($desired);
		}

		return $source;
	}
}

new Twoo_GitHub_Updater(
	defined('TWOO_PLUGIN_FILE') ? TWOO_PLUGIN_FILE : dirname(__DIR__) . '/twoo-performant-woo.php',
	apply_filters('twoo_updater_github_owner', 'rwkyyy'),
	apply_filters('twoo_updater_github_repo', '2performant-woocommerce'),
	apply_filters('twoo_updater_asset_name', '2performant-woocommerce.zip')
);
