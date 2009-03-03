#!/bin/bash

# This script creates symlinks from the local GIT repo into your EE install. It also copies some of the extension icons.

dirname=`dirname "$0"`

echo "Creating symlinks for LG Member List"
echo ""
echo "Enter the path to your ExpressionEngine Install without a trailing slash [ENTER]:"
read ee_path
echo "Enter your system folder name [ENTER]:"
read ee_system_folder

ln -s "$dirname"/system/extensions/ext.lg_member_list.php "$ee_path"/"$ee_system_folder"/extensions/ext.lg_member_list.php
ln -s "$dirname"/system/language/english/lang.lg_member_list.php "$ee_path"/"$ee_system_folder"/language/english/lang.lg_member_list.php
