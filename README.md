# Convert NextGEN Galleries to WordPress

This is a WordPress plugin that will convert image galleries in the [NextGEN Gallery](http://wordpress.org/plugins/nextgen-gallery/) format into the native WordPress gallery format.

A list is generated showing all the posts and pages that are using NextGEN galleries. They can be converted one page at a time, or all the pages can be converted at once.

The [nggallery] shortcodes are replaced with corresponding [gallery] shortcodes, and all the images are copied over from the NextGEN image area into to the usual WordPress Media center.

## How to use it

* Backup your database and files.
* Install and activate this plugin. If you're into Git, you can do a `git clone` in your plugins folder, or alternatively you can download the raw version of the convert-nextgen-galleries.php file and put it in your plugins folder.
* Browse to the 'Convert NextGEN Galleries' page under 'Settings'.
* Click 'List galleries to convert' to see what galleries will be converted.
* Click 'Convert' on a singe post, to convert just that post, or 'Convert all galleries', to convert all.

It's a good idea to run the conversion on one page as a test before converting all your galleries. It may take some time to convert if there are lots of images, so you may want to do the conversion a few pages at a time. 

The NextGEN galleries and images remain untouched so, if you want to revert, you can manually restore the [nggallery] shortcodes and the NextGEN galleries will work as they did before.

If you're happy with the results, and all galleries have been converted, you can uninstall the NextGEN plugin and remove any NextGEN gallery folders and database tables.


## How it works

The plugin works by finding all the posts and pages with [nggallery] shortcodes inside. It then loops over all those shortcodes and finds the corresponding NextGEN galleries for them. All the images in those galleries are copied and added to the regular WordPress Media library. The [nggallery] shortcodes are replaced with [gallery] shortcodes including the ids of those images converted.


## Additional options

The admin page can be found at:

```
/wp-admin/options-general.php?page=convert-nextgen-galleries.php
```

You can append additional arguments to that URL to perform the different operations.

### post

If you want to work the galleries on one specific post, you can use `&post=`. 

For example, to list all galleries on page 43 you can use:

```
/wp-admin/options-general.php?page=convert-nextgen-galleries.php&action=list&post=43
```

Then to convert those galleries you can use: 

```
/wp-admin/options-general.php?page=convert-nextgen-galleries.php&action=convert&post=43
```

### max_num

If you want work the galleries on the first 4 posts, you can use `&max_num=`. 

For example, to list all galleries on the first 4 pages you can use:

```
/wp-admin/options-general.php?page=convert-nextgen-galleries.php&action=list&max_num=4
```

Then to convert those galleries you can use: 

```
/wp-admin/options-general.php?page=convert-nextgen-galleries.php&action=convert&max_num=4
```


## Screenshot

Here is a screenshot of the admin screen listing the images to convert:

![Listing galleries to convert](https://raw.github.com/stefansenk/convert-nextgen-galleries/master/screenshot-listing-galleries.png)
