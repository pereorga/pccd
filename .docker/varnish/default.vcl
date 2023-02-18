vcl 4.1;

backend default {
    .host = "pccd";
    .port = "80";
}

sub vcl_recv {

    # Unset x-cache variable (will be used later).
    unset req.http.x-cache;

    # Do not cache admin pages.
    if (req.url ~ "^/admin/") {
        return(pass);
    }

    # Remove all cookies.
    if (req.http.Cookie) {
        unset req.http.Cookie;
    }

    # Ensure static/compressed files are not cached.
    if (req.url ~ "\.(avif|gif|jpg|jpeg|mp3|png|webp)$") {
        return(pass);
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

    # Do not cache the object if no encoding is set. It is likely a bot /
    # outdated browser, and so we do not care about page speed.
    # We may want to remove this if we get too much traffic that impacts the
    # web server, but otherwise, I believe using fewer Varnish cache resources
    # is preferable.
    if (!req.http.Accept-Encoding) {
        return(pass);
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

    # Remove some unnecessary/conflicting HTTP headers.
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Age;
    unset resp.http.Date;

    if (obj.uncacheable) {
        set req.http.x-cache = req.http.x-cache + " uncacheable" ;
    }
    else {
        set req.http.x-cache = req.http.x-cache + " cached" ;
    }

    set resp.http.x-cache = req.http.x-cache;
}
