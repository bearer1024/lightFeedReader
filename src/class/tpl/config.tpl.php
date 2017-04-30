<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend>LIGHT feed Reader information</legend>

                  <div class="control-group">
                    <label class="control-label" for="title">Feed reader title</label>
                    <div class="controls">
                      <input type="text" id="title" name="title" value="<?php echo $kfctitle; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Public/private reader</label>
                    <div class="controls">
                      <label for="publicReader">
                        <input type="radio" id="publicReader" name="public" value="1" <?php echo ($kfcpublic? 'checked="checked"' : ''); ?>/>
                        Public light feed
                      </label>
                      <label for="privateReader">
                        <input type="radio" id="privateReader" name="public" value="0" <?php echo (!$kfcpublic? 'checked="checked"' : ''); ?>/>
                        Private light feed
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="shaarli">Shaarli url</label>
                  <div class="control-group">
                    <label class="control-label" for="menuView">View</label>
                    <div class="controls">
                      <input type="text" id="menuView" name="menuView" value="<?php echo empty($kfcmenu['menuView'])?'0':$kfcmenu['menuView']; ?>">
                      <span class="help-block">If you want to switch between list and expanded view</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuListFeeds">List of feeds</label>
                    <div class="controls">
                      <input type="text" id="menuListFeeds" name="menuListFeeds" value="<?php echo empty($kfcmenu['menuListFeeds'])?'0':$kfcmenu['menuListFeeds']; ?>">
                      <span class="help-block">If you want to show or hide list of feeds</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuFilter">Filter</label>
                    <div class="controls">
                      <input type="text" id="menuFilter" name="menuFilter" value="<?php echo empty($kfcmenu['menuFilter'])?'0':$kfcmenu['menuFilter']; ?>">
                      <span class="help-block">If you want to filter all or unread items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuOrder">Order</label>
                    <div class="controls">
                      <input type="text" id="menuOrder" name="menuOrder" value="<?php echo empty($kfcmenu['menuOrder'])?'0':$kfcmenu['menuOrder']; ?>">
                      <span class="help-block">If you want to order by newer or older items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUpdate">Update</label>
                    <div class="controls">
                      <input type="text" id="menuUpdate" name="menuUpdate" value="<?php echo empty($kfcmenu['menuUpdate'])?'0':$kfcmenu['menuUpdate']; ?>">
                      <span class="help-block">If you want to update all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuRead">Read</label>
                    <div class="controls">
                      <input type="text" id="menuRead" name="menuRead" value="<?php echo empty($kfcmenu['menuRead'])?'0':$kfcmenu['menuRead']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as read</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUnread">Unread</label>
                    <div class="controls">
                      <input type="text" id="menuUnread" name="menuUnread" value="<?php echo empty($kfcmenu['menuUnread'])?'0':$kfcmenu['menuUnread']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as unread</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuEdit">Edit</label>
                    <div class="controls">
                      <input type="text" id="menuEdit" name="menuEdit" value="<?php echo empty($kfcmenu['menuEdit'])?'0':$kfcmenu['menuEdit']; ?>">
                      <span class="help-block">If you want to edit all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuAdd">Add</label>
                    <div class="controls">
                      <input type="text" id="menuAdd" name="menuAdd" value="<?php echo empty($kfcmenu['menuAdd'])?'0':$kfcmenu['menuAdd']; ?>">
                      <span class="help-block">If you want to add a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuHelp">Help</label>
                    <div class="controls">
                      <input type="text" id="menuHelp" name="menuHelp" value="<?php echo empty($kfcmenu['menuHelp'])?'0':$kfcmenu['menuHelp']; ?>">
                      <span class="help-block">If you want to add a link to the help</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>light feed paging menu preferences</legend>
                  <div class="control-group">
                    <label class="control-label" for="pagingItem">Item</label>
                    <div class="controls">
                      <input type="text" id="pagingItem" name="pagingItem" value="<?php echo empty($kfcpaging['pagingItem'])?'0':$kfcpaging['pagingItem']; ?>">
                      <span class="help-block">If you want to go previous and next item </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingPage">Page</label>
                    <div class="controls">
                      <input type="text" id="pagingPage" name="pagingPage" value="<?php echo empty($kfcpaging['pagingPage'])?'0':$kfcpaging['pagingPage']; ?>">
                      <span class="help-block">If you want to go previous and next page </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingByPage">Items by page</label>
                    <div class="controls">
                      <input type="text" id="pagingByPage" name="pagingByPage" value="<?php echo empty($kfcpaging['pagingByPage'])?'0':$kfcpaging['pagingByPage']; ?>">
                      <span class="help-block">If you want to modify number of items by page</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingMarkAs">Mark as read</label>
                    <div class="controls">
                      <input type="text" id="pagingMarkAs" name="pagingMarkAs" value="<?php echo empty($kfcpaging['pagingMarkAs'])?'0':$kfcpaging['pagingMarkAs']; ?>">
                      <span class="help-block">If you add a mark as read button into paging</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
              </form><br>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
