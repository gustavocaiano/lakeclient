<?php


it('can activate', function () {
    //Arrange
    $service = app(\Tests\Services\TestService::class);
    expect($service->isLicensed())->not()->toBeTrue();

    //Act
    $service->activate();

    //Assert

    expect($service->isLicensed())->toBeTrue();

});
