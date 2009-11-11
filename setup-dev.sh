#!/bin/bash

NAME="hak_tinymce"
CURRENT_DIR=$(pwd)
DEV_DIR="$CURRENT_DIR/dev"
TINYMCE_DIR="tiny_mce"
DISTRO_DIR="$CURRENT_DIR/tinymce-distro/jscripts/$TINYMCE_DIR"
COMPRESSOR_DIR="$CURRENT_DIR/tinymce_compressor_php"
DEV_PLUGIN_DIR="$DEV_DIR/$TINYMCE_DIR/plugins"
TXPIMAGE_DIR="$CURRENT_DIR/txpimage"

echo "removing old dev folder "
rm -r $DEV_DIR
echo "creating new dev folder"
mkdir $DEV_DIR
echo "copying distro folder to dev root"
cp -r $DISTRO_DIR $DEV_DIR
echo "copying compressor to dev dir"
cp $COMPRESSOR_DIR/tiny_mce_gzip.* $DEV_DIR/$TINYMCE_DIR
echo "symlnk txpimage into tinymce plugins directory"
ln -s $TXPIMAGE_DIR $DEV_PLUGIN_DIR/txpimage
echo "create dev script in dev folder"
cpp -DDEV -P plugin.php $DEV_DIR/$NAME.php
echo "syminking code include"
ln -s $CURRENT_DIR/code.php $DEV_DIR/code.php
