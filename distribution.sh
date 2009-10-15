#!/bin/bash

NAME="hak_tinymce"
CURRENT_DIR=$(pwd)
DIST_DIR="$NAME-dist"
TPL_DIR="$CURRENT_DIR/template"
TINYMCE_DIR="tiny_mce"
DISTRO_DIR="$CURRENT_DIR/tinymce-distro/jscripts/$TINYMCE_DIR"
COMPRESSOR_DIR="$CURRENT_DIR/tinymce_compressor_php"
DEV_PLUGIN_DIR="$DIST_DIR/$TINYMCE_DIR/plugins"
TXPIMAGE_DIR="$CURRENT_DIR/txpimage"

echo "removing old dist folder "
rm -r $DIST_DIR
echo "creating new dist folder"
mkdir $DIST_DIR
echo "copying distro folder to dist root"
cp -r $DISTRO_DIR $DIST_DIR
echo "copying compressor to dev dir"
cp $COMPRESSOR_DIR/tiny_mce_gzip.* $DIST_DIR/$TINYMCE_DIR
echo "copying txpimage into tinymce directory"
cp -r $TXPIMAGE_DIR $DEV_PLUGIN_DIR
echo "create distribution"
cp $NAME.php $TPL_DIR
cd $TPL_DIR
php $NAME.php > $NAME.txt
mv $NAME.txt $CURRENT_DIR/$DIST_DIR
rm $NAME.php
cd $CURRENT_DIR
cp install.txt $DIST_DIR
zip -r $NAME.zip $DIST_DIR
rm -r $DIST_DIR
