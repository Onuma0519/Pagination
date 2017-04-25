<?php

/**
 * Pagination
 * @copyright (c) 2017 Hironori Onuma
 * @license The MIT License
 */
class Pagination
{
	protected $_base_url = null;

	protected $_query_string = null;

	protected $_omission_first_link = null;

	protected $_omission_last_link = null;

	protected $_first_button = true;

	protected $_prev_button = true;

	protected $_next_button = true;

	protected $_last_button = true;

	protected $_max_before_link = 3;

	protected $_max_next_link = 3;

	protected $_total = null;

	protected $_key_name = 'page';
	
	protected $_current_page = null;

	protected $_offset = null;

	protected $_limit = 20;

	protected $_template = array(
		'wrapper'           => '<ul class="pagination">{pagination}</ul>',
		'current'           => '<li class="current">{current_page}</li>',
		'link'              => '<li><a href="{uri}">{page}</a></li>',
		'before_omission'   => '<li class="omission">...</li>',
		'after_omission'    => '<li class="omission">...</li>',
		'first'             => '<li class="first"><a href="{uri}">&lt;&lt;</a></li>',
		'first-inactive'    => '<li class="first inactive">&lt;&lt;</li>',
		'last'              => '<li class="last"><a href="{uri}">&gt;&gt;</a></li>',
		'last-inactive'     => '<li class="last inactive">&gt;&gt;</li>',
		'previous'          => '<li class="previous"><a href="{uri}">&lt;</a></li>',
		'previous-inactive' => '<li class="previous inactive">&lt;</li>',
		'next'              => '<li class="next"><a href="{uri}">&gt;</a></li>',
		'next-inactive'     => '<li class="next inactive">&gt;</li>',
		'first_label'       => '',
		'last_label'        => '',
	);

	protected $_order = array(
		'first',
		'previous',
		'link',
		'next',
		'last',
	);

	public function __construct()
	{
		$params = $_GET;
		is_array($params) or $params = array();
		if (array_key_exists($this->_key_name, $params))
		{
			unset($params[$this->_key_name]);
		}
		$this->_query_string = http_build_query($params);
		if ($info = parse_url($_SERVER['REQUEST_URI']))
		{
			isset($info['scheme']) or $info['scheme'] = (empty($_SERVER["HTTPS"]) ? "http://" : "https://");
			isset($info['host']) or $info['host'] = $_SERVER['HTTP_HOST'];
			$this->_base_url = $info['scheme'].$info['host'].$info['path'];
		}
	}

	public function setConfig(array $configs = array())
	{
		foreach ($configs as $property => $value)
		{
			$property = "_{$property}";
			$this->{$property} = $value;
		}
		return $this;
	}

	public function html()
	{
		$wrapper = isset($this->_template['wrapper']) ? $this->_template['wrapper'] : '{pagination}';
		$html = '';
		$this->calc();
		$prevActive = ( $this->_current_page > 1 );
		$nextActive = ( ceil($this->_total / $this->_limit) > $this->_current_page );

		$query_string = '?' . $this->_query_string;
		$op_amp = ($query_string !== '?');

		$base_url = $this->_base_url.$query_string;

		// ページ番号上限値
		$max_page_number = ceil($this->_total / $this->_limit);

		// 前ページリンクの最大数を設定
		if (is_null($this->_max_before_link))
		{
			$_max_before_link = $this->_current_page - 1;
		}

		// 後ページリンクの最大数を設定
		if (is_null($this->_max_next_link))
		{
			$_max_next_link = ceil($this->_total / $this->_limit) - $this->_current_page;
		}

		foreach ($this->_order as $value)
		{
			$tmp = '';
			$no  = '';
			$uri = '';
			switch ($value)
			{
				case 'first':
					if ($this->_first_button)
					{
						$tmp = $prevActive ? $this->_template['first'] : $this->_template['first-inactive'];
						$no  = 1;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
					}
					break;
				case 'previous':
					if ($this->_prev_button)
					{
						$tmp = $prevActive ? $this->_template['previous'] : $this->_template['previous-inactive'];
						$no  = $this->_current_page - 1;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
					}
					break;
				case 'link':
					$page_start = $this->_current_page - $this->_max_before_link;
					if ($this->_omission_first_link)
					{
						$page_count = ($this->_current_page - $this->_max_before_link - $this->_omission_first_link - 1);
						if ($page_count > 0)
						{
							$page_count > $this->_omission_first_link and $page_count = $this->_omission_first_link;
							for ($i = 1; $i <= $page_count; $i++)
							{
								$tmp = $this->_template['link'];
								$no  = $i;
								$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
								$tmp = str_replace('{page}', $no, $tmp);
								$tmp = str_replace('{uri}', $uri, $tmp);
								$html .= $tmp;
							}
							$html .= $this->_template['before_omission'];
						}
						else
						{
							$page_start = 1;
						}
					}
					$page_start < 1 and $page_start = 1;
					for ($i = $page_start; $i < $this->_current_page; $i++)
					{
						$tmp = $this->_template['link'];
						$no  = $i;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
						$tmp = str_replace('{page}', $no, $tmp);
						$tmp = str_replace('{uri}', $uri, $tmp);
						$html .= $tmp;
					}

					$uri = $this->joinUrl($base_url, $this->_key_name, $this->_current_page, $op_amp);
					$tmp = $this->_template['current'];
					$tmp = str_replace('{current_page}', $this->_current_page, $tmp);
					$tmp = str_replace('{uri}', $uri, $tmp);
					$html .= $tmp;
					$tmp = '';

					$page_count = ($this->_current_page + $this->_max_next_link);
					$page_count > $max_page_number and $page_count = $max_page_number;
					if ($this->_omission_last_link)
					{
						if (($page_count + $this->_omission_last_link + 1) >= $max_page_number)
						{
							$page_count = $max_page_number;
						}
					}
					for ($i = ($this->_current_page + 1); $i <= $page_count; $i++)
					{
						$tmp = $this->_template['link'];
						$no  = $i;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
						$tmp = str_replace('{page}', $no, $tmp);
						$tmp = str_replace('{uri}', $uri, $tmp);
						$html .= $tmp;
					}
					if ( ($page_count < $max_page_number) and $this->_omission_last_link)
					{
						$html .= $this->_template['after_omission'];
						$page_count = $max_page_number - $this->_omission_last_link + 1;
						for ($i = $page_count; $i <= $max_page_number; $i++)
						{
							$tmp = $this->_template['link'];
							$no  = $i;
							$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
							$tmp = str_replace('{page}', $no, $tmp);
							$tmp = str_replace('{uri}', $uri, $tmp);
							$html .= $tmp;
						}
					}
					break;
				case 'next':
					if ($this->_next_button)
					{
						$tmp = $nextActive ? $this->_template['next'] : $this->_template['next-inactive'];
						$no  = $this->_current_page + 1;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
					}
					break;
				case 'last':
					if ($this->_last_button)
					{
						$tmp = $nextActive ? $this->_template['last'] : $this->_template['last-inactive'];
						$no  = $max_page_number;
						$uri = $this->joinUrl($base_url, $this->_key_name, $no, $op_amp);
					}
					break;
			}
			if ($value !== 'link')
			{
				$tmp = str_replace('{page}', $no, $tmp);
				$tmp = str_replace('{uri}', $uri, $tmp);
				$html .= $tmp;
			}
		}

		$html = $this->_template['first_label'].$html.$this->_template['last_label'];
		return str_replace('{pagination}', $html, $wrapper);
	}

	protected function joinUrl($base_url, $key_name, $no, $op_amp)
	{
		return $op_amp ? "{$base_url}&{$key_name}={$no}" : "{$base_url}{$key_name}={$no}";
	}

	protected function calc()
	{
		if (is_null($this->_current_page))
		{
			$this->_current_page = isset($_GET[$this->_key_name]) ? (int) $_GET[$this->_key_name] : 1;
		}

		if (is_null($this->_offset))
		{
			$this->_offset = ($this->_current_page * $this->_limit) - $this->_limit;
		}
	}

	public function __isset($property)
	{
		$property = "_{$property}";
		return isset($this->{$property});
	}

	public function __unset($property)
	{
		$property = "_{$property}";
		unset($this->{$property});
	}

	public function __set($property, $value)
	{
		$property = "_{$property}";
		$this->{$property} = $value;
	}

	public function __get($property)
	{
		$property = "_{$property}";
		if (isset($this->{$property}))
		{
			switch ($property)
			{
				case '_offset':
				case '_current_page':
					$this->calc();
					break;
			}
			return $this->{$property};
		}
		return null;
	}
}
