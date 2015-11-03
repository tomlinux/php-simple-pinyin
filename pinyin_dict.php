<?php
/**
 * 汉字转拼音类。参考python 的 python-pinyin 实现
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license, which is
 * available through the world-wide-web at the following URI:
 * http://github.com/ztxmao/php-simple-pinyin/LICENSE
 *
 * @category  PinyinDict
 * @package   PinyinDict
 * @author    ztxmao <chengjunxiong@outlook.com>
 * @copyright 2009 - 2014 Eoghan O'Brien
 * @version   1.4
 * @link      http://github.com/ztxmao/php-simple-pinyin
 */
class PinyinDict
{
    private $_pinyin_dict= '';
    private $_words_pinyin_dict = '';
    private $_word_len_limit = 10; //词语长度限制
    private $_apc_user_cache_key = 'zeus:pinyin:dict';
    private $_pinyin_dict_data = array();
    
    public function __construct() {
        $this->_pinyin_dict = dirname(__FILE__) . '/dict/pinyin.json';
        $this->_words_pinyin_dict = dirname(__FILE__) . '/dict/words_pinyin.json';
    }
    public function get_pinyin($query)
    {
        if(!preg_match('/[\x{4e00}-\x{9fa5}]/u', $query)) {
            return $query;
        }
        $query_len = mb_strlen($query, 'utf-8');
        if($query_len > $this->_word_len_limit) {
            return '';
        }
        $match = array();
        preg_match_all('/[\x{4e00}-\x{9fa5}]|[a-zA-Z]|\d/u', $query, $match);
        $word_arr = array();
        $words = '';
        foreach($match[0] as $val) {
            if(trim($val)) {
                $word_arr[] = trim($val);
                $words .= $val;
            }
        }
        //按照常见词语获取多音字
        // TODO 先有分词在获取多音字就完美了。
        $pinyin_ret = $this->_get_words_pinyin($words);
        //获取单个字的拼音
        $pinyin_ret = $pinyin_ret ? $pinyin_ret : $this->_get_single_word_pinyin($word_arr);
        return $pinyin_ret;
    }
    // 词语的字典放入redis
    public function save_dict_to_cache()
    {
        if(! file_exists($this->_words_pinyin_dict)) {
            return '';
        }
        $dict = file_get_contents($this->_words_pinyin_dict);
        $dict = json_decode($dict, true);
        $redis_dao = new Redis();
        $redis_dao->connect('127.0.0.1', '6379');
        foreach($dict as $word => $pinyins) {
            $pinyin_str = '';
            foreach($pinyins as $pinyin) {
                if($pinyin) {
                    $pinyin_str = $pinyin_str . ' ' . current($pinyin);
                }
            }
            $pinyin_str = trim($pinyin_str);
            $redis_dao->hSet('words:pinyin:dict', $word, $pinyin_str);
        }
    }
    private function _get_words_pinyin($words)
    {
        $redis_dao = new Redis();
        $redis_dao->connect('127.0.0.1', '6379');
        $result = $redis_dao->hGet('words:pinyin:dict', $words);
        return $result ? $result : '';
    }
    
    private function _get_single_word_pinyin(array $words)
    {
        $pinyin_ret = '';
        if(!$this->_pinyin_dict_data) {
            if(function_exists('apc_fetch')) {
                $dict = apc_fetch($this->_apc_user_cache_key);
            } else {
                $dict = false;
            }
            if(! $dict) {
                if(! file_exists($this->_pinyin_dict)) {
                    return '';
                }
                $dict = file_get_contents($this->_pinyin_dict);
                $dict = json_decode($dict, true);
                if(function_exists('apc_add')) {
                    apc_store($this->_apc_user_cache_key, $dict, 86400 * 7);
                }
            }
            $this->_pinyin_dict_data = $dict;
        } 
        $pinyin_ret = '';
        foreach($words as $word) {
            $word_unicode = json_encode(array($word));
            $word_unicode_arr = explode('\u', $word_unicode);
            $word_unicode_num = empty($word_unicode_arr[1]) ? 'no_num' : base_convert($word_unicode_arr[1], 16, 10);
            $pinyins = empty($this->_pinyin_dict_data[$word_unicode_num]) ? $word : $this->_pinyin_dict_data[$word_unicode_num];
            list($pinyin) = explode(',', $pinyins);
            $pinyin_ret = $pinyin_ret . ' ' . $pinyin;
        }
        return  trim($pinyin_ret);
    }
}
