<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455\MessageHandler;

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageHandler\CloseFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class CloseFrameHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testItProcessCloseFrame()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['88', '02', '03', 'E8'])));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    public function testItCloseWithProtocolErrorWhenFrameIsNotValid()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR)->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['F8', '02', '03', 'E8'])));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    public function testItClosesWithProtocolErrorOnWrongCloseCode()
    {
        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR)->willReturn(new Frame());
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['88', '02', '00', 'F7'])));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }
}
