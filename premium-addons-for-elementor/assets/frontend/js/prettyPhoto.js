!(function (w) {
    function b() {
        var t = location.href;
        return (hashtag = -1 !== t.indexOf("#prettyPhoto") && decodeURI(t.substring(t.indexOf("#prettyPhoto") + 1, t.length))), (hashtag = hashtag && hashtag.replace(/<|>/g, "")), hashtag;
    }
    function k(t, e) {
        var i = "[\\?&]" + (t = t.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]")) + "=([^&#]*)",
            p = new RegExp(i).exec(e);
        return null == p ? "" : p[1];
    }
    (w.prettyPhoto = { version: "3.1.6" }),
        (w.fn.prettyPhoto = function (e) {
            function i() {
                w(".pp_loaderIcon").hide(),
                    (projectedTop = scroll_pos.scrollTop + (v / 2 - r.containerHeight / 2)),
                    projectedTop < 0 && (projectedTop = 0),
                    $ppt.fadeTo(settings.animation_speed, 1),
                    $pp_pic_holder.find(".pp_content").animate({ height: r.contentHeight, width: r.contentWidth }, settings.animation_speed),
                    $pp_pic_holder.animate({ top: projectedTop, left: y / 2 - r.containerWidth / 2 < 0 ? 0 : y / 2 - r.containerWidth / 2, width: r.containerWidth }, settings.animation_speed, function () {
                        $pp_pic_holder.find(".pp_hoverContainer,#fullResImage").height(r.height).width(r.width),
                            $pp_pic_holder.find(".pp_fade").fadeIn(settings.animation_speed),
                            isSet && "image" == s(pp_images[set_position]) ? $pp_pic_holder.find(".pp_hoverContainer").show() : $pp_pic_holder.find(".pp_hoverContainer").hide(),
                            settings.allow_expand && (r.resized ? w("a.pp_expand,a.pp_contract").show() : w("a.pp_expand").hide()),
                            !settings.autoplay_slideshow || m || d || w.prettyPhoto.startSlideshow(),
                            settings.changepicturecallback(),
                            (d = !0);
                    }),
                    isSet && settings.overlay_gallery && "image" == s(pp_images[set_position])
                        ? ((itemWidth = 57),
                            (navWidth = "facebook" == settings.theme || "pp_default" == settings.theme ? 50 : 30),
                            (itemsPerPage = Math.floor((r.containerWidth - 100 - navWidth) / itemWidth)),
                            (itemsPerPage = itemsPerPage < pp_images.length ? itemsPerPage : pp_images.length),
                            (totalPage = Math.ceil(pp_images.length / itemsPerPage) - 1),
                            0 == totalPage ? ((navWidth = 0), $pp_gallery.find(".pp_arrow_next,.pp_arrow_previous").hide()) : $pp_gallery.find(".pp_arrow_next,.pp_arrow_previous").show(),
                            (galleryWidth = itemsPerPage * itemWidth),
                            (fullGalleryWidth = pp_images.length * itemWidth),
                            $pp_gallery
                                .css("margin-left", -(galleryWidth / 2 + navWidth / 2))
                                .find("div:first")
                                .width(galleryWidth + 5)
                                .find("ul")
                                .width(fullGalleryWidth)
                                .find("li.selected")
                                .removeClass("selected"),
                            (goToPage = Math.floor(set_position / itemsPerPage) < totalPage ? Math.floor(set_position / itemsPerPage) : totalPage),
                            w.prettyPhoto.changeGalleryPage(goToPage),
                            $pp_gallery_li.filter(":eq(" + set_position + ")").addClass("selected"))
                        : $pp_pic_holder.find(".pp_content").unbind("mouseenter mouseleave"),
                    e.ajaxcallback();
            }
            function p(t) {
                $pp_pic_holder.find("#pp_full_res object,#pp_full_res embed").css("visibility", "hidden"),
                    $pp_pic_holder.find(".pp_fade").fadeOut(settings.animation_speed, function () {
                        w(".pp_loaderIcon").show(), t();
                    });
            }
            function o(t, e) { //width,height
                resized = false;

                a(t, e);

                imageWidth = t, imageHeight = e;

                if (((y < g || v < _) && doresize && settings.allow_resize && !f)) {

                    resized = true, fitting = false;

                    var currentDevice = elementorFrontend.getCurrentDeviceMode(),
                        sideGap = !currentDevice.includes('tablet') && !currentDevice.includes('mobile') ? 200 : 40;

                    while (!fitting) {
                        if (y < g) {
                            imageWidth = y - sideGap;
                            imageHeight = (e / t) * imageWidth;
                        } else if (v < _) {
                            imageHeight = v - 200;
                            imageWidth = (t / e) * imageHeight;
                        } else {
                            fitting = true;
                        }

                        _ = imageHeight, g = imageWidth;
                    }

                    if (y < g || v < _) {
                        o(g, _)
                    }

                    a(imageWidth, imageHeight);

                }

                return {
                    width: Math.floor(imageWidth),
                    height: Math.floor(imageHeight),
                    containerHeight: Math.floor(_),
                    containerWidth: Math.floor(g) + 2 * settings.horizontal_padding,
                    contentHeight: Math.floor(h),
                    contentWidth: Math.floor(c),
                    resized: resized,
                };
            }
            function a(t, e) {
                (t = parseFloat(t)),
                    (e = parseFloat(e)),
                    ($pp_details = $pp_pic_holder.find(".pp_details")),
                    $pp_details.width(t),
                    (detailsHeight = parseFloat($pp_details.css("marginTop")) + parseFloat($pp_details.css("marginBottom"))),
                    ($pp_details = $pp_details.clone().addClass(settings.theme).width(t).appendTo(w("body")).css({ position: "absolute", top: -1e4 })),
                    (detailsHeight += $pp_details.height()),
                    (detailsHeight = detailsHeight <= 34 ? 36 : detailsHeight),
                    $pp_details.remove(),
                    ($pp_title = $pp_pic_holder.find(".ppt")),
                    $pp_title.width(t),
                    (titleHeight = parseFloat($pp_title.css("marginTop")) + parseFloat($pp_title.css("marginBottom"))),
                    ($pp_title = $pp_title.clone().appendTo(w("body")).css({ position: "absolute", top: -1e4 })),
                    (titleHeight += $pp_title.height()),
                    $pp_title.remove(),
                    (h = e + detailsHeight),
                    (c = t),
                    (_ = h + titleHeight + $pp_pic_holder.find(".pp_top").height() + $pp_pic_holder.find(".pp_bottom").height()),
                    (g = t);
            }
            function s(t) {
                return t.match(/youtube\.com\/watch/i) || t.match(/youtu\.be/i)
                    ? "youtube"
                    : t.match(/vimeo\.com/i)
                        ? "vimeo"
                        : t.match(/\b.mov\b/i)
                            ? "quicktime"
                            : t.match(/\b.mp4\b/i)
                                ? "localvideo"
                                : t.match(/\b.swf\b/i)
                                    ? "flash"
                                    : t.match(/\biframe=true\b/i)
                                        ? "iframe"
                                        : t.match(/\bajax=true\b/i)
                                            ? "ajax"
                                            : t.match(/\bcustom=true\b/i)
                                                ? "custom"
                                                : "#" == t.substr(0, 1)
                                                    ? "inline"
                                                    : "image";
            }
            function n() {
                if (doresize && "undefined" != typeof $pp_pic_holder) {
                    if (
                        ((scroll_pos = t()),
                            (contentHeight = $pp_pic_holder.height()),
                            (contentwidth = $pp_pic_holder.width()),
                            (projectedTop = v / 2 + scroll_pos.scrollTop - contentHeight / 2),
                            projectedTop < 0 && (projectedTop = 0),
                            contentHeight > v)
                    )
                        return;
                    $pp_pic_holder.css({ top: projectedTop, left: y / 2 + scroll_pos.scrollLeft - contentwidth / 2 });
                }
            }
            function t() {
                return self.pageYOffset
                    ? { scrollTop: self.pageYOffset, scrollLeft: self.pageXOffset }
                    : document.documentElement && document.documentElement.scrollTop
                        ? { scrollTop: document.documentElement.scrollTop, scrollLeft: document.documentElement.scrollLeft }
                        : document.body
                            ? { scrollTop: document.body.scrollTop, scrollLeft: document.body.scrollLeft }
                            : void 0;
            }
            function l() {
                if (
                    (settings.social_tools && (facebook_like_link = settings.social_tools.replace("{location_href}", encodeURIComponent(location.href))),
                        (settings.markup = settings.markup.replace("{pp_social}", "")),
                        w("body").append(settings.markup),
                        ($pp_pic_holder = w(".pp_pic_holder")),
                        ($ppt = w(".ppt")),
                        ($pp_overlay = w("div.pp_overlay")),
                        isSet && settings.overlay_gallery)
                ) {
                    (currentGalleryPage = 0), (toInject = "");
                    for (var t = 0; t < pp_images.length; t++)
                        (img_src = pp_images[t].match(/\b(jpg|jpeg|png|gif)\b/gi) ? ((classname = ""), pp_images[t]) : ((classname = "default"), "")),
                            (toInject += "<li class='" + classname + "'><a href='#'><img src='" + img_src + "' width='50' alt='' /></a></li>");
                    (toInject = settings.gallery_markup.replace(/{gallery}/g, toInject)),
                        $pp_pic_holder.find("#pp_full_res").after(toInject),
                        ($pp_gallery = w(".pp_pic_holder .pp_gallery")),
                        ($pp_gallery_li = $pp_gallery.find("li")),
                        $pp_gallery.find(".pp_arrow_next").click(function () {
                            return w.prettyPhoto.changeGalleryPage("next"), w.prettyPhoto.stopSlideshow(), !1;
                        }),
                        $pp_gallery.find(".pp_arrow_previous").click(function () {
                            return w.prettyPhoto.changeGalleryPage("previous"), w.prettyPhoto.stopSlideshow(), !1;
                        }),
                        $pp_pic_holder.find(".pp_content").hover(
                            function () {
                                $pp_pic_holder.find(".pp_gallery:not(.disabled)").fadeIn();
                            },
                            function () {
                                $pp_pic_holder.find(".pp_gallery:not(.disabled)").fadeOut();
                            }
                        ),
                        (itemWidth = 57),
                        $pp_gallery_li.each(function (t) {
                            w(this)
                                .find("a")
                                .click(function () {
                                    return w.prettyPhoto.changePage(t), w.prettyPhoto.stopSlideshow(), !1;
                                });
                        });
                }
                settings.slideshow &&
                    ($pp_pic_holder.find(".pp_nav").prepend('<a href="#" class="pp_play"></a>'),
                        $pp_pic_holder.find(".pp_nav .pp_play").click(function () {
                            return w.prettyPhoto.startSlideshow(), !1;
                        })),
                    $pp_pic_holder.attr("class", "pp_pic_holder " + settings.theme),
                    $pp_overlay.css({ opacity: 0, height: w(document).height(), width: w(window).width() }).bind("click", function () {
                        settings.modal || w.prettyPhoto.close();
                    }),
                    w("a.pp_close").bind("click", function () {
                        return w.prettyPhoto.close(), !1;
                    }),
                    settings.allow_expand &&
                    w("a.pp_expand").bind("click", function (t) {
                        return (
                            (doresize = w(this).hasClass("pp_expand") ? (w(this).removeClass("pp_expand").addClass("pp_contract"), !1) : (w(this).removeClass("pp_contract").addClass("pp_expand"), !0)),
                            p(function () {
                                w.prettyPhoto.open();
                            }),
                            !1
                        );
                    }),
                    $pp_pic_holder.find(".pp_previous, .pp_nav .pp_arrow_previous").bind("click", function () {
                        return w.prettyPhoto.changePage("previous"), w.prettyPhoto.stopSlideshow(), !1;
                    }),
                    $pp_pic_holder.find(".pp_next, .pp_nav .pp_arrow_next").bind("click", function () {
                        return w.prettyPhoto.changePage("next"), w.prettyPhoto.stopSlideshow(), !1;
                    }),
                    n();
            }
            e = jQuery.extend(
                {
                    hook: "rel",
                    animation_speed: "fast",
                    ajaxcallback: function () { },
                    slideshow: 5e3,
                    autoplay_slideshow: !1,
                    opacity: 0.8,
                    show_title: !0,
                    allow_resize: !0,
                    allow_expand: !0,
                    default_width: 500,
                    default_height: 344,
                    counter_separator_label: "/",
                    theme: "pp_default",
                    horizontal_padding: 20,
                    hideflash: !1,
                    wmode: "opaque",
                    autoplay: !0,
                    modal: !1,
                    deeplinking: !0,
                    overlay_gallery: !0,
                    overlay_gallery_max: 30,
                    keyboard_shortcuts: !0,
                    changepicturecallback: function () { },
                    callback: function () { },
                    ie6_fallback: !0,
                    markup:
                        '<div class="pp_pic_holder"> \t\t\t\t\t\t<div class="ppt">&nbsp;</div> \t\t\t\t\t\t<div class="pp_top"> \t\t\t\t\t\t\t<div class="pp_left"></div> \t\t\t\t\t\t\t<div class="pp_middle"></div> \t\t\t\t\t\t\t<div class="pp_right"></div> \t\t\t\t\t\t</div> \t\t\t\t\t\t<div class="pp_content_container"> \t\t\t\t\t\t\t<div class="pp_left"> \t\t\t\t\t\t\t<div class="pp_right"> \t\t\t\t\t\t\t\t<div class="pp_content"> \t\t\t\t\t\t\t\t\t<div class="pp_loaderIcon"></div> \t\t\t\t\t\t\t\t\t<div class="pp_fade"> \t\t\t\t\t\t\t\t\t\t<a href="#" class="pp_expand" title="Expand the image"></a> \t\t\t\t\t\t\t\t\t\t<div class="pp_hoverContainer"> \t\t\t\t\t\t\t\t\t\t\t<a class="pp_next" href="#">next</a> \t\t\t\t\t\t\t\t\t\t\t<a class="pp_previous" href="#"></a> \t\t\t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t\t\t\t<div id="pp_full_res"></div> \t\t\t\t\t\t\t\t\t\t<div class="pp_details"> \t\t\t\t\t\t\t\t\t\t\t<div class="pp_nav"> \t\t\t\t\t\t\t\t\t\t\t\t<a href="#" class="pp_arrow_previous"></a> \t\t\t\t\t\t\t\t\t\t\t\t<p class="currentTextHolder">0/0</p> \t\t\t\t\t\t\t\t\t\t\t\t<a href="#" class="pp_arrow_next"></a> \t\t\t\t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t\t\t\t\t<p class="pp_description"></p> \t\t\t\t\t\t\t\t\t\t\t<div class="pp_social">{pp_social}</div> \t\t\t\t\t\t\t\t\t\t\t<a class="pp_close" href="#"></a> \t\t\t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t</div> \t\t\t\t\t\t</div> \t\t\t\t\t\t<div class="pp_bottom"> \t\t\t\t\t\t\t<div class="pp_left"></div> \t\t\t\t\t\t\t<div class="pp_middle"></div> \t\t\t\t\t\t\t<div class="pp_right"></div> \t\t\t\t\t\t</div> \t\t\t\t\t</div> \t\t\t\t\t<div class="pp_overlay"></div>',
                    gallery_markup:
                        '<div class="pp_gallery"> \t\t\t\t\t\t\t\t<a href="#" class="pp_arrow_previous"></a> \t\t\t\t\t\t\t\t<div> \t\t\t\t\t\t\t\t\t<ul> \t\t\t\t\t\t\t\t\t\t{gallery} \t\t\t\t\t\t\t\t\t</ul> \t\t\t\t\t\t\t\t</div> \t\t\t\t\t\t\t\t<a href="#" class="pp_arrow_next"></a> \t\t\t\t\t\t\t</div>',
                    image_markup: '<img id="fullResImage" src="{path}" />',
                    flash_markup:
                        '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="{width}" height="{height}"><param name="wmode" value="{wmode}" /><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="{path}" /><embed src="{path}" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="{width}" height="{height}" wmode="{wmode}"></embed></object>',
                    quicktime_markup:
                        '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" height="{height}" width="{width}"><param name="src" value="{path}"><param name="autoplay" value="{autoplay}"><param name="type" value="video/quicktime"><embed src="{path}" height="{height}" width="{width}" autoplay="{autoplay}" type="video/quicktime" pluginspage="http://www.apple.com/quicktime/download/"></embed></object>',
                    localvideo_markup: '<video width="{width}" height="{height}" autoplay controls><source src="{path}" type="video/mp4"></video>',
                    iframe_markup: '<iframe src ="{path}" width="{width}" height="{height}" frameborder="no" allowfullscreen="true"></iframe>',
                    inline_markup: '<div class="pp_inline">{content}</div>',
                    custom_markup: "",
                    social_tools:
                        '<div class="twitter"><a href="http://twitter.com/share" class="twitter-share-button" data-count="none">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script></div><div class="facebook"><iframe src="//www.facebook.com/plugins/like.php?locale=en_US&href={location_href}&amp;layout=button_count&amp;show_faces=true&amp;width=500&amp;action=like&amp;font&amp;colorscheme=light&amp;height=23" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:500px; height:23px;" allowTransparency="true"></iframe></div>',
                },
                e
            );
            var r,
                d,
                h,
                c,
                _,
                g,
                m,
                u = this,
                f = !1,
                v = w(window).height(),
                y = w(window).width();
            return (
                (doresize = !0),
                (scroll_pos = t()),
                w(window)
                    .unbind("resize.prettyphoto")
                    .bind("resize.prettyphoto", function () {
                        n(), (v = w(window).height()), (y = w(window).width()), "undefined" != typeof $pp_overlay && $pp_overlay.height(w(document).height()).width(y);
                    }),
                e.keyboard_shortcuts &&
                w(document)
                    .unbind("keydown.prettyphoto")
                    .bind("keydown.prettyphoto", function (t) {
                        if ("undefined" != typeof $pp_pic_holder && $pp_pic_holder.is(":visible"))
                            switch (t.keyCode) {
                                case 37:
                                    w.prettyPhoto.changePage("previous"), t.preventDefault();
                                    break;
                                case 39:
                                    w.prettyPhoto.changePage("next"), t.preventDefault();
                                    break;
                                case 27:
                                    settings.modal || w.prettyPhoto.close(), t.preventDefault();
                            }
                    }),
                (w.prettyPhoto.initialize = function () {
                    return (
                        (settings = e),
                        "pp_default" == settings.theme && (settings.horizontal_padding = 16),
                        (theRel = w(this).attr(settings.hook)),
                        (galleryRegExp = /\[(?:.*)\]/),
                        (isSet = !!galleryRegExp.exec(theRel)),
                        (pp_images = isSet
                            ? jQuery.map(u, function (t, e) {
                                if (-1 != w(t).attr(settings.hook).indexOf(theRel)) return w(t).attr("href");
                            })
                            : w.makeArray(w(this).attr("href"))),
                        (pp_titles = isSet
                            ? jQuery.map(u, function (t, e) {
                                if (-1 != w(t).attr(settings.hook).indexOf(theRel)) return w(t).find("img").attr("alt") ? w(t).find("img").attr("alt") : "";
                            })
                            : w.makeArray(w(this).find("img").attr("alt"))),
                        (pp_descriptions = isSet
                            ? jQuery.map(u, function (t, e) {
                                if (-1 != w(t).attr(settings.hook).indexOf(theRel)) return w(t).attr("title") ? w(t).attr("title") : "";
                            })
                            : w.makeArray(w(this).attr("title"))),
                        pp_images.length > settings.overlay_gallery_max && (settings.overlay_gallery = !1),
                        (set_position = jQuery.inArray(w(this).attr("href"), pp_images)),
                        (rel_index = isSet ? set_position : w("a[" + settings.hook + "^='" + theRel + "']").index(w(this))),
                        l(),
                        settings.allow_resize &&
                        w(window).bind("scroll.prettyphoto", function () {
                            n();
                        }),
                        w.prettyPhoto.open(),
                        !1
                    );
                }),
                (w.prettyPhoto.open = function (t) {
                    return (
                        "undefined" == typeof settings &&
                        ((settings = e),
                            (pp_images = w.makeArray(t)),
                            (pp_titles = arguments[1] ? w.makeArray(arguments[1]) : w.makeArray("")),
                            (pp_descriptions = arguments[2] ? w.makeArray(arguments[2]) : w.makeArray("")),
                            (isSet = 1 < pp_images.length),
                            (set_position = arguments[3] ? arguments[3] : 0),
                            l(t.target)),
                        settings.hideflash && w("object,embed,iframe[src*=youtube],iframe[src*=vimeo]").css("visibility", "hidden"),
                        1 < w(pp_images).size() ? w(".pp_nav").show() : w(".pp_nav").hide(),
                        w(".pp_loaderIcon").show(),
                        !settings.deeplinking || ("undefined" != typeof theRel && (location.hash = theRel + "/" + rel_index + "/")),
                        settings.social_tools && ((facebook_like_link = settings.social_tools.replace("{location_href}", encodeURIComponent(location.href))), $pp_pic_holder.find(".pp_social").html(facebook_like_link)),
                        $ppt.is(":hidden") && $ppt.css("opacity", 0).show(),
                        $pp_overlay.show().fadeTo(settings.animation_speed, settings.opacity),
                        $pp_pic_holder.find(".currentTextHolder").text(set_position + 1 + settings.counter_separator_label + w(pp_images).size()),
                        void 0 !== pp_descriptions[set_position] && "" != pp_descriptions[set_position]
                            ? $pp_pic_holder.find(".pp_description").show().html(unescape(pp_descriptions[set_position]))
                            : $pp_pic_holder.find(".pp_description").hide(),
                        (movie_width = parseFloat(k("width", pp_images[set_position])) ? k("width", pp_images[set_position]) : settings.default_width.toString()),
                        (movie_height = parseFloat(k("height", pp_images[set_position])) ? k("height", pp_images[set_position]) : settings.default_height.toString()),
                        (f = !1),
                        -1 != movie_height.indexOf("%") && ((movie_height = parseFloat((w(window).height() * parseFloat(movie_height)) / 100 - 150)), (f = !0)),
                        -1 != movie_width.indexOf("%") && ((movie_width = parseFloat((w(window).width() * parseFloat(movie_width)) / 100 - 150)), (f = !0)),
                        $pp_pic_holder.fadeIn(function () {
                            switch (
                            (settings.show_title && "" != pp_titles[set_position] && void 0 !== pp_titles[set_position] ? $ppt.html(unescape(pp_titles[set_position])) : $ppt.html("&nbsp;"),
                                (imgPreloader = ""),
                                (skipInjection = !1),
                                s(pp_images[set_position]))
                            ) {
                                case "image":

                                    var selfHostedURL = pp_images[set_position];

                                    selfHostedURL = selfHostedURL.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(
                                        /"/g, "&quot;");

                                    (imgPreloader = new Image()),
                                        (nextImage = new Image()),
                                        isSet && set_position < w(pp_images).size() - 1 && (nextImage.src = pp_images[set_position + 1]),
                                        (prevImage = new Image()),
                                        isSet && pp_images[set_position - 1] && (prevImage.src = pp_images[set_position - 1]),
                                        ($pp_pic_holder.find("#pp_full_res")[0].innerHTML = settings.image_markup.replace(/{path}/g, selfHostedURL)),
                                        (imgPreloader.onload = function () {
                                            (r = o(imgPreloader.width, imgPreloader.height)), i();
                                        }),
                                        (imgPreloader.onerror = function () {
                                            alert("Image cannot be loaded. Make sure the path is correct and image exist."), w.prettyPhoto.close();
                                        }),
                                        (imgPreloader.src = pp_images[set_position]);
                                    break;
                                case "youtube":
                                    (r = o(movie_width, movie_height)),
                                        (movie_id = k("v", pp_images[set_position])),
                                        "" == movie_id &&
                                        ((movie_id = pp_images[set_position].split("youtu.be/")),
                                            (movie_id = movie_id[1]),
                                            0 < movie_id.indexOf("?") && (movie_id = movie_id.substr(0, movie_id.indexOf("?"))),
                                            0 < movie_id.indexOf("&") && (movie_id = movie_id.substr(0, movie_id.indexOf("&")))),
                                        (movie = "//www.youtube.com/embed/" + movie_id),
                                        k("rel", pp_images[set_position]) ? (movie += "?rel=" + k("rel", pp_images[set_position])) : (movie += "?rel=1"),
                                        settings.autoplay && (movie += "&autoplay=1"),
                                        (toInject = settings.iframe_markup
                                            .replace(/{width}/g, r.width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{wmode}/g, settings.wmode)
                                            .replace(/{path}/g, movie));
                                    break;
                                case "vimeo":
                                    (r = o(movie_width, movie_height)), (movie_id = pp_images[set_position]);
                                    var t = movie_id.match(/http(s?):\/\/(www\.)?vimeo.com\/(\d+)/);
                                    if (t) {
                                        (movie = "//player.vimeo.com/video/" + t[3] + "?title=0&amp;byline=0&amp;portrait=0"),
                                            settings.autoplay && (movie += "&autoplay=1;");
                                    } else {
                                        movie = movie_id;
                                    }
                                    (vimeo_width = r.width + "/embed/?moog_width=" + r.width),
                                        (toInject = settings.iframe_markup
                                            .replace(/{width}/g, vimeo_width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{path}/g, movie));
                                    break;
                                case "quicktime":
                                    ((r = o(movie_width, movie_height)).height += 15),
                                        (r.contentHeight += 15),
                                        (r.containerHeight += 15),
                                        (toInject = settings.quicktime_markup
                                            .replace(/{width}/g, r.width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{wmode}/g, settings.wmode)
                                            .replace(/{path}/g, pp_images[set_position])
                                            .replace(/{autoplay}/g, settings.autoplay));
                                    break;
                                case "localvideo":

                                    var selfHostedURL = pp_images[set_position];

                                    selfHostedURL = selfHostedURL.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(
                                        /"/g, "&quot;");

                                    ((r = o(movie_width, movie_height)).height += 15),
                                        (r.contentHeight += 15),
                                        (r.containerHeight += 15),
                                        (toInject = settings.localvideo_markup
                                            .replace(/{width}/g, r.width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{wmode}/g, settings.wmode)
                                            .replace(/{path}/g, selfHostedURL)
                                            .replace(/{autoplay}/g, settings.autoplay));
                                    break;
                                case "flash":
                                    (r = o(movie_width, movie_height)),
                                        (flash_vars = pp_images[set_position]),
                                        (flash_vars = flash_vars.substring(pp_images[set_position].indexOf("flashvars") + 10, pp_images[set_position].length)),
                                        (filename = pp_images[set_position]),
                                        (filename = filename.substring(0, filename.indexOf("?"))),
                                        (toInject = settings.flash_markup
                                            .replace(/{width}/g, r.width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{wmode}/g, settings.wmode)
                                            .replace(/{path}/g, filename + "?" + flash_vars));
                                    break;
                                case "iframe":
                                    (r = o(movie_width, movie_height)),
                                        (frame_url = pp_images[set_position]),
                                        (frame_url = frame_url.substr(0, frame_url.indexOf("iframe") - 1)),
                                        (toInject = settings.iframe_markup
                                            .replace(/{width}/g, r.width)
                                            .replace(/{height}/g, r.height)
                                            .replace(/{path}/g, frame_url));
                                    break;
                                case "ajax":
                                    (doresize = !1),
                                        (r = o(movie_width, movie_height)),
                                        (doresize = !0),
                                        (skipInjection = !0),
                                        w.get(pp_images[set_position], function (t) {
                                            (toInject = settings.inline_markup.replace(/{content}/g, t)), ($pp_pic_holder.find("#pp_full_res")[0].innerHTML = toInject), i();
                                        });
                                    break;
                                case "custom":
                                    (r = o(movie_width, movie_height)), (toInject = settings.custom_markup);
                                    break;
                                case "inline":
                                    (myClone = w(pp_images[set_position])
                                        .clone()
                                        .append('<br clear="all" />')
                                        .css({ width: settings.default_width })
                                        .wrapInner('<div id="pp_full_res"><div class="pp_inline"></div></div>')
                                        .appendTo(w("body"))
                                        .show()),
                                        (doresize = !1),
                                        (r = o(w(myClone).width(), w(myClone).height())),
                                        (doresize = !0),
                                        w(myClone).remove(),
                                        (toInject = settings.inline_markup.replace(/{content}/g, w(pp_images[set_position]).html()));
                            }
                            imgPreloader || skipInjection || (($pp_pic_holder.find("#pp_full_res")[0].innerHTML = toInject), i());
                        }),
                        !1
                    );
                }),
                (w.prettyPhoto.changePage = function (t) {
                    (currentGalleryPage = 0),
                        "previous" == t ? --set_position < 0 && (set_position = w(pp_images).size() - 1) : "next" == t ? ++set_position > w(pp_images).size() - 1 && (set_position = 0) : (set_position = t),
                        (rel_index = set_position),
                        (doresize = doresize || !0),
                        settings.allow_expand && w(".pp_contract").removeClass("pp_contract").addClass("pp_expand"),
                        p(function () {
                            w.prettyPhoto.open();
                        });
                }),
                (w.prettyPhoto.changeGalleryPage = function (t) {
                    "next" == t ? ++currentGalleryPage > totalPage && (currentGalleryPage = 0) : "previous" == t ? --currentGalleryPage < 0 && (currentGalleryPage = totalPage) : (currentGalleryPage = t),
                        (slide_speed = "next" == t || "previous" == t ? settings.animation_speed : 0),
                        (slide_to = currentGalleryPage * (itemsPerPage * itemWidth)),
                        $pp_gallery.find("ul").animate({ left: -slide_to }, slide_speed);
                }),
                (w.prettyPhoto.startSlideshow = function () {
                    void 0 === m
                        ? ($pp_pic_holder
                            .find(".pp_play")
                            .unbind("click")
                            .removeClass("pp_play")
                            .addClass("pp_pause")
                            .click(function () {
                                return w.prettyPhoto.stopSlideshow(), !1;
                            }),
                            (m = setInterval(w.prettyPhoto.startSlideshow, settings.slideshow)))
                        : w.prettyPhoto.changePage("next");
                }),
                (w.prettyPhoto.stopSlideshow = function () {
                    $pp_pic_holder
                        .find(".pp_pause")
                        .unbind("click")
                        .removeClass("pp_pause")
                        .addClass("pp_play")
                        .click(function () {
                            return w.prettyPhoto.startSlideshow(), !1;
                        }),
                        clearInterval(m),
                        (m = void 0);
                }),
                (w.prettyPhoto.close = function () {
                    $pp_overlay.is(":animated") ||
                        (w.prettyPhoto.stopSlideshow(),
                            $pp_pic_holder.stop().find("object,embed").css("visibility", "hidden"),
                            w("div.pp_pic_holder,div.ppt,.pp_fade").fadeOut(settings.animation_speed, function () {
                                w(this).remove();
                            }),
                            $pp_overlay.fadeOut(settings.animation_speed, function () {
                                settings.hideflash && w("object,embed,iframe[src*=youtube],iframe[src*=vimeo]").css("visibility", "visible"),
                                    w(this).remove(),
                                    w(window).unbind("scroll.prettyphoto"),
                                    -1 !== location.href.indexOf("#prettyPhoto") && (location.hash = "prettyPhoto"),
                                    settings.callback(),
                                    (doresize = !0),
                                    (d = !1),
                                    delete settings;
                            }));
                }),
                !pp_alreadyInitialized &&
                b() &&
                ((pp_alreadyInitialized = !0),
                    (hashIndex = b()),
                    (hashRel = hashIndex),
                    (hashIndex = hashIndex.substring(hashIndex.indexOf("/") + 1, hashIndex.length - 1)),
                    (hashRel = hashRel.substring(0, hashRel.indexOf("/"))),
                    setTimeout(function () {
                        w("a[" + e.hook + "^='" + hashRel + "']:eq(" + hashIndex + ")").trigger("click");
                    }, 50)),
                this.unbind("click.prettyphoto").bind("click.prettyphoto", w.prettyPhoto.initialize)
            );
        });
})(jQuery);
var pp_alreadyInitialized = !1;
