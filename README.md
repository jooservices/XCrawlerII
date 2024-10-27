# XCrawlerII

## Client
Factory for `GuzzleHttp\Client` with
- Mocking
- Retries
- Logging
- History

To use mocking
- `appendResponse` || `appendException`

### ClientManager
IClient is a interface for `Client` wrapper.

Wrapper will handle request / respond and exceptions.

It also used for any extra thing like request logs

`ClientManager` pre registered with `BaseClient`

$client = app(ClientManager::class)->getClient(BaseClient::class); // Will return BaseClient instance

By default BaseClient will
- Handle request with logging ( `request_logs` MongoDB )
- Respond with `BaseResponse`
- - `BaseResponse` will parse data based on `Content-Type`
