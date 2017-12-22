<?php defined("MODERA_KEY")|| die(); ?>            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2><?php echo $data["SEARCH"]; ?></h2>
                    </div>
                    <!--boxcontent-->
                    <div class="boxcontent">
                        <!--mainSearch-->
                        <div class="mainSearch">
                            <form action="<?php echo $data["SELF"]; ?>" method="get" class="jNice">
                                <div class="sTitle"><?php echo $data["SEARCH_INFO"]; ?>:</div>
                                <input type="text" name="search_query" value="<?php echo $data["SEARCH_QUERY"]; ?>" />
                                 <input type="submit" value="<?php echo $data["SEARCH"]; ?>" />
                            </form>
                        </div>
                        <!--/mainSearch-->
                    </div>
                    <!--/boxcontent-->
                </div>
            </div>
            <!--/box-->
