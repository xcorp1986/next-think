<?php

namespace Think\Image;

use Think\Image;

interface IImage
{
    /**
     * 打开一幅图像
     *
     * @param  string $imgName 图片路径
     *
     * @return $this          当前图片处理库对象
     */
    public function open($imgName);

    /**
     * 保存图片
     *
     * @param  string $imgName 图片保存名称
     * @param  string $type 图片类型
     * @param  int $quality 图像质量
     * @param  bool $interlace 是否对JPEG类型图片设置隔行扫描
     *
     * @return $this             当前图片处理库对象
     */
    public function save($imgName, $type = null, $quality = 80, $interlace = true);

    /**
     * 返回图片宽度
     * @return int 图片宽度
     */
    public function width();

    /**
     * 返回图片高度
     * @return int 图片高度
     */
    public function height();

    /**
     * 返回图像类型
     * @return string 图片类型
     */
    public function type();

    /**
     * 返回图像MIME类型
     * @return string 图像MIME类型
     */
    public function mime();

    /**
     * 返回图像尺寸数组 0 - 图片宽度，1 - 图片高度
     * @return array 图片尺寸
     */
    public function size();

    /**
     * 裁剪图片
     *
     * @param  int $w 裁剪区域宽度
     * @param  int $h 裁剪区域高度
     * @param  int $x 裁剪区域x坐标
     * @param  int $y 裁剪区域y坐标
     * @param  int $width 图片保存宽度
     * @param  int $height 图片保存高度
     *
     * @return $this          当前图片处理库对象
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null);

    /**
     * 生成缩略图
     *
     * @param  int $width 缩略图最大宽度
     * @param  int $height 缩略图最大高度
     * @param  int $type 缩略图裁剪类型
     *
     * @return $this          当前图片处理库对象
     */
    public function thumb($width, $height, $type = Image::IMAGE_THUMB_SCALE);

    /**
     * 添加水印
     *
     * @param  string $source 水印图片路径
     * @param  int $locate 水印位置
     * @param  int $alpha 水印透明度
     *
     * @return $this          当前图片处理库对象
     */
    public function water($source, $locate = Image::IMAGE_WATER_SOUTHEAST, $alpha = 80);

    /**
     * 图像添加文字
     *
     * @param  string $text 添加的文字
     * @param  string $font 字体路径
     * @param  int $size 字号
     * @param  string $color 文字颜色
     * @param  int $locate 文字写入位置
     * @param  int $offset 文字相对当前位置的偏移量
     * @param  int $angle 文字倾斜角度
     *
     * @return $this          当前图片处理库对象
     */
    public function text(
        $text,
        $font,
        $size,
        $color = '#00000000',
        $locate = Image::IMAGE_WATER_SOUTHEAST,
        $offset = 0,
        $angle = 0
    );


}