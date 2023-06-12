vcl 4.1;

backend default {
    .host = "pccd";
    .port = "80";
}

sub vcl_recv {

    # Disallow other methods.
    if (req.method != "GET" && req.method != "HEAD" && req.method != "POST" && req.method != "OPTIONS") {
        return (synth(405, "Method Not Allowed"));
    }

    # Unset x-cache header (will be used later).
    unset req.http.x-cache;

    # Only cache GET and HEAD requests.
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    # Do not cache admin pages.
    if (req.url ~ "^/admin/") {
        return (pass);
    }

    # Remove all cookies.
    if (req.http.Cookie) {
        unset req.http.Cookie;
    }

    # Unset Accept-Encoding header from compressed files.
    # This will also be used later for determining whether to cache the object.
    if (req.url ~ "\.(avif|gif|jpg|jpeg|mp3|png|webp)$") {
        unset req.http.Accept-Encoding;
    }

    # Normalize Accept-Encoding to increase cache hits.
    if (req.http.Accept-Encoding) {
        if (req.http.Accept-Encoding ~ "br") {

            # Deliver Brotli if supported.
            set req.http.Accept-Encoding = "br";
        }
        elsif (req.http.Accept-Encoding ~ "gzip") {

            # Otherwise, deliver gzip if supported.
            set req.http.Accept-Encoding = "gzip";
        }
        else {

            # Otherwise, unset header and deliver the uncompressed object.
            unset req.http.Accept-Encoding;
        }
    }

    # Do not cache the object if no encoding is set. It is likely:
    #
    #   1) A static/compressed file (see above).
    #   2) A bad configured bot or scrapper, so we do not care about speed.
    #   3) An outdated browser, so we do not care about speed.
    #
    # We may want to remove this if we get too much traffic that impacts the
    # web server, but otherwise, it seems that using fewer Varnish cache
    # resources is preferable.
    if (!req.http.Accept-Encoding) {

        # But do cache AVIF and WEBP files, which should be the majority of the
        # requests of static files, and still represent a relatively small
        # amount of bytes.
        if (!(req.url ~ "\.(avif|webp)$")) {
            return (pass);
        }
    }
}

sub vcl_hit {
    set req.http.x-cache = "hit";
    if (obj.ttl <= 0s && obj.grace > 0s) {
        set req.http.x-cache = "hit graced";
    }
}

sub vcl_miss {
    set req.http.x-cache = "miss";
}

sub vcl_pass {
    set req.http.x-cache = "pass";
}

sub vcl_pipe {
    set req.http.x-cache = "pipe uncacheable";
}

sub vcl_deliver {

    # Remove some unnecessary HTTP headers.
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Age;

    if (obj.uncacheable) {
        set req.http.x-cache = req.http.x-cache + " uncacheable" ;
    }
    else {
        set req.http.x-cache = req.http.x-cache + " cached" ;
    }

    set resp.http.x-cache = req.http.x-cache;
}
