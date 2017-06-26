<?php

namespace Think;

/**
 * 分页类
 * Class Page
 * @package Think
 */
class Page
{
    /**
     * @var array|int $firstRow 起始行数
     */
    public $firstRow;
    /**
     * @var array|int $listRows 列表每页显示行数
     */
    public $listRows;
    /**
     * @var array $parameter 分页跳转时要带的参数
     */
    public $parameter;
    /**
     * @var array $totalRows 总行数
     */
    public $totalRows;
    /**
     * @var int $totalPages 分页总页面数
     */
    public $totalPages;
    /**
     * @var int $rollPage 分页栏每页显示的页数
     */
    public $rollPage = 11;
    /**
     * @var bool $lastSuffix 最后一页是否显示总页数
     */
    public $lastSuffix = true;

    /**
     * @var mixed|string $p 分页参数名
     */
    protected $p = 'p';
    /**
     * @var string $url 当前链接URL
     */
    protected $url = '';
    /**
     * @var int $nowPage 当前页
     */
    protected $nowPage = 1;

    /**
     * @var array $config 分页显示定制
     */
    protected $config = [
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        'prev'   => '<<',
        'next'   => '>>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    ];

    /**
     * @param array $totalRows 总的记录数
     * @param int $listRows 每页显示记录数
     * @param array $parameter 分页跳转的参数
     */
    public function __construct($totalRows, $listRows = 20, $parameter = [])
    {
        //设置分页参数名称
        C('VAR_PAGE') && $this->p = C('VAR_PAGE');
        /* 基础设置 */
        //设置总记录数
        $this->totalRows = $totalRows;
        //设置每页显示行数
        $this->listRows = $listRows;
        $this->parameter = empty($parameter) ? $_GET : $parameter;
        $this->nowPage = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage = $this->nowPage > 0 ? $this->nowPage : 1;
        $this->firstRow = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 定制分页链接设置
     *
     * @param string $name 设置名称
     * @param string $value 设置值
     */
    public function setConfig($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     *
     * @param  int $page 页码
     *
     * @return string
     */
    private function url($page)
    {
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show()
    {
        if (0 == $this->totalRows) {
            return '';
        }

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        //总页数
        $this->totalPages = ceil($this->totalRows / $this->listRows);
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="prev" href="'.$this->url(
                $up_row
            ).'">'.$this->config['prev'].'</a>' : '';

        //下一页
        $down_row = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a class="next" href="'.$this->url(
                $down_row
            ).'">'.$this->config['next'].'</a>' : '';

        //第一页
        $the_first = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
            $the_first = '<a class="first" href="'.$this->url(1).'">'.$this->config['first'].'</a>';
        }

        //最后一页
        $the_end = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
            $the_end = '<a class="end" href="'.$this->url($this->totalPages).'">'.$this->config['last'].'</a>';
        }

        //数字连接
        $link_page = '';
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if ($page > 0 && $page != $this->nowPage) {

                if ($page <= $this->totalPages) {
                    $link_page .= '<a class="num" href="'.$this->url($page).'">'.$page.'</a>';
                } else {
                    break;
                }
            } else {
                if ($page > 0 && $this->totalPages != 1) {
                    $link_page .= '<span class="current">'.$page.'</span>';
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            [
                '%HEADER%',
                '%NOW_PAGE%',
                '%UP_PAGE%',
                '%DOWN_PAGE%',
                '%FIRST%',
                '%LINK_PAGE%',
                '%END%',
                '%TOTAL_ROW%',
                '%TOTAL_PAGE%',
            ],
            [
                $this->config['header'],
                $this->nowPage,
                $up_page,
                $down_page,
                $the_first,
                $link_page,
                $the_end,
                $this->totalRows,
                $this->totalPages,
            ],
            $this->config['theme']
        );

        return "<div>{$page_str}</div>";
    }
}
