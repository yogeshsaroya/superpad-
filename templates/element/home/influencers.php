<?php
$data = $this->Data->getInfluencers();
if (!$data->isEmpty()) {
    //media_type
?>
    <style>
        .influ .our-team {
            border-bottom: 1px solid #00325a;
        }

        .influ .pic img {
            border-radius: 10%;
        }

        .influ .title {
            margin-bottom: 15px;
        }

        .influ .our-team:hover .pic:after {
            background: transparent;
        }

        .influ .pic:before {
            content: "";
            border-radius: 0;
            background: gray;
            position: absolute;
            bottom: 135%;

        }

        .influ .pic {
            width: 150px;
            height: auto;
            margin-bottom: 50px;
            
        }
    </style>
    <section id="partners_list" class="brand-section section-space <?php echo (isset($bg_color) ?  $bg_color : null); ?>">
        <div class="container">
            <div class="section-head text-center">
                <h2 class="mb-3">SuperPAD Influencers</h2>
            </div><!-- end section-head -->
            <div class="row g-gs">
                <?php

                foreach ($data as $list) { ?>

                    <div class="col-lg-3 col-md-6 col-sm-6 influ">
                        <div class="our-team">
                            <div class="pic">
                                <?php echo $this->Html->image(SITEURL . 'cdn/influencers/' . $list->logo, ['alt' => 'logo', 'width' => 100]); ?>
                            </div>
                            <div class="team-content">
                                <h3 class="title"><?php echo $list->title; ?></h3>
                                <?php if (!empty($list->url)) { ?>
                                    <a href="<?php echo $list->url; ?>" target="_blank" class="btn btn-lg btn-outline-dark">
                                        <?php
                                        if ($list->media_type == 'YouTube') {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16"><path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.007 2.007 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31.4 31.4 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408L6.4 5.209z"/></svg>';
                                        } elseif ($list->media_type == 'Telegram') {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telegram" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.287 5.906c-.778.324-2.334.994-4.666 2.01-.378.15-.577.298-.595.442-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294.26.006.549-.1.868-.32 2.179-1.471 3.304-2.214 3.374-2.23.05-.012.12-.026.166.016.047.041.042.12.037.141-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8.154 8.154 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629.093.06.183.125.27.187.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.426 1.426 0 0 0-.013-.315.337.337 0 0 0-.114-.217.526.526 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09z"/></svg>';
                                        }
                                        elseif ($list->media_type == 'Twitter') {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16"><path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/></svg>';
                                        } ?>

                                        <?php echo $list->media_type; ?>
                                    </a>
                                <?php } ?>
                            </div>

                        </div>
                        <br>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
<?php } ?>