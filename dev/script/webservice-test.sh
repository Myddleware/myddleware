#!/usr/bin/env bash


## Auth
username=admin
accessKey=SqiydxtjogkTAznU
endpoint=http://localhost:30081/webservice.php
token=$(curl -fsSL "${endpoint}?operation=getchallenge&username=${username}" | cut -d'"' -f8)
accessKey=$(echo -n "${token}${accessKey}" | md5sum | head -c 32)
sessionName=$(curl -fsSL "${endpoint}" -d "operation=login" -d "username=${username}" -d "accessKey=${accessKey}" | cut -d'"' -f8)


curl -fsSL "${endpoint}" \
  -d "sessionName=${sessionName}" \
  -d "operation=dbrecord_crud_row" \
  -d "name=ProductPricebook" \
  -d "mode=select" \
  -d "element={
        \"limint\": \"10\",
        \"offset\": \"0\"
  }"

echo ""
