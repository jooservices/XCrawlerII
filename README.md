# XCrawlerII

## Client
Factory for `GuzzleHttp\Client` with
- Mocking
- Retries
- Logging
- History

To use with mocking
- `appendResponse` || `appendException`

Note :// These mocking used for requests by times not by endpoint / payload

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

### How to mock Client
In case you want to develop UnitTest with specific Client request by endpoint / payload
- Mock GuzzleHttp Client to cover your request
- Mock Factory::make to return mocked GuzzleHttp above

### OneJav
- We have our own Onejav Client
  - With endpoint provided
  - With custom user agent
- Models
  - OnejavReference
  - JavMovie

### Udemy
- Command `udemy:sync-my-courses {token}`
  - Dispatch job `SyncMyCoursesJob`
    -  Create UdemyCourse record 
        -  Dispatch event UdemyCourseCreatedEvent 
          -  Dispatch job `SyncCurriculumItemsJob` for fetching Curriculum items
             - When item created will dispatch event and check if ALL items are fetch
               - When all items are fetch than dispatch event `CourseReadyForStudy`. Now we are ready for study
                 -  In this event we will process all items and complete it 
