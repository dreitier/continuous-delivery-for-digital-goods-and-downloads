#!/bin/bash
username=admin
application_password="OvTN Yo1l QccU mwZt ChLS hbo5"
api_key=$(echo -n "$username:$application_password" | base64)
url="http://localhost/wordpress/index.php"
product_id=110

curl -v --insecure -XPOST -H "Content-type: application/json" \
	--user "$username:$application_password" \
	-d '{
        "url": "/var/local/new_version",
        "version": "1.0.6",
        "meta": {
            "readme": "Hello world"
        }
    }' "$url/wp-json/continuous-delivery/v1/product/$product_id/release"
