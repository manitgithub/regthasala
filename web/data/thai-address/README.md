# Thai address data

Source: https://kamthorn.github.io/thailand-provinces/v2/address.json
Project and integration example: https://kamthorn.github.io/thailand-provinces/
Downloaded: 2026-11-04 (Asia/Bangkok client date).
Bundle version: 2; source hash: 7d1b5eb335e4.

The unmodified address dataset is hosted locally. Search runs in the browser;
applicant queries are not transmitted to the upstream site. The PHP validator
uses this same file to resolve the selected subdistrict code to its canonical
subdistrict, district, province and postal code. Update frontend and backend data
together when refreshing this dataset.

The original helper is vendored unchanged in resources/vendor/thai-address.cjs
from https://kamthorn.github.io/thailand-provinces/thai-address.js . Original
attribution and header are retained. No additional licensing terms are asserted.
