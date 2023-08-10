vcl 4.1;

backend default {
    .host = "web";
    .port = "80";
}

sub vcl_recv {

    # Disallow other methods.
    if (req.method != "GET" && req.method != "HEAD" && req.method != "POST" && req.method != "OPTIONS") {
        return (synth(405, "Method Not Allowed"));
    }

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

            # Otherwise, unset header to deliver the uncompressed object.
            unset req.http.Accept-Encoding;
        }
    }

    # Do not cache the object if no encoding is set, due to these probable scenarios:
    #
    #   1) It may be a static or compressed file (as mentioned above, but see below).
    #   2) It could be a poorly configured bot or scraper, where speed isn't a concern.
    #   3) It might be an obsolete browser, where speed doesn't really matter.
    #
    # This strategy might be reconsidered if we experience overwhelming traffic that strains the web server. However, as
    # it stands, it seems that minimizing the usage of Varnish cache resources is beneficial.
    if (!req.http.Accept-Encoding) {

        # Make an exception for caching AVIF and WEBP files. These files should constitute the majority of static file
        # requests, yet they represent a relatively modest data volume.
        if (!(req.url ~ "\.(avif|webp)$")) {
            return (pass);
        }
    }

    # Do the same for gzip encoding, due to these 2 probable scenarios:
    #
    #   1) The request might be originating from a bot or web scraper, in which case performance isn't a top priority.
    #   2) The request could come from an outdated browser, again where speed is less crucial.
    #
    # Once again, the goal here is to optimize the usage of Varnish memory resources, even if it comes at the expense of
    # increased CPU usage for Apache/PHP.
    if (req.http.Accept-Encoding == "gzip") {
        return (pass);
    }
}

sub vcl_deliver {

    # Remove some unnecessary HTTP headers.
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Age;
}
