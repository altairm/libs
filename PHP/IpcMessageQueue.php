<?php
/**
 * Implimentation of ipc message queue class
 *
 * Usage:
 *
 *    $obj = new  IpcMessageQueue([array $options]);
 *
 *    $obj->connect('queue name');
 *
 *    $obj->add('blablabla');
 *
 *    $message = $obj->get();
 *
 * 
 * @author michael
 */
class IpcMessageQueue {
    /**
     * message queue handler
     * @var Resource
     */
    protected $_queue = null;
    /**
     * need serialize?
     * @var bool
     */
    protected $_serialize = true;
    /**
     * blocking send?
     * @var bool
     */
    protected $_blockSend = false;
    /**
     * message type
     * @var int
     */
    protected $_msgType = 1;
    /**
     * message max size (bytes)
     * @var int
     */
    protected $_maxSize = 16384;
    /**
     * recive options (MSG_IPC_NOWAIT | MSG_NOERROR | MSG_EXCEPT)
     * @var int
     */
    protected $_receiveOpt = MSG_IPC_NOWAIT;
    /**
     * last error
     * @var string
     */
    protected $_lastError = '';

    /**
     * Options:
     *
     *    [serialize] = true|false - need serialize?
     *
     *    [block_send] = true|false - blocking send?
     *
     *    [message_type] = <int> - message type
     *
     *    [maxsize] = <int> - message max size
     *
     *    [receive_option] = <int> - receive option (MSG_IPC_NOWAIT | MSG_NOERROR | MSG_EXCEPT)
     *
     * @param array $options
     * @return IpcMessageQueue
     */
    public function __construct($options = array()) {
        $this->setOptions($options);
    }
    /**
     * Options:
     *
     *    [serialize] = true|false - need serialize?
     *
     *    [block_send] = true|false - blocking send?
     *
     *    [message_type] = <int> - message type
     *
     *    [maxsize] = <int> - message max size
     *
     *    [receive_option] = <int> - receive option (MSG_IPC_NOWAIT | MSG_NOERROR | MSG_EXCEPT)
     *
     * @param array $options
     */
    public function setOptions($options) {
        if ( isset($options['serialize']) ) {
            $this->_serialize = (bool) $options['serialize'];
        }
        if ( isset($options['block_send']) ) {
            $this->_blockSend = (bool) $options['block_send'];
        }
        if ( isset($options['message_type']) ) {
            $this->_msgType = (int) $options['message_type'];
        }
        if ( isset($options['maxsize']) ) {
            $this->_maxSize = (int) $options['maxsize'];
        }
        if ( isset($options['receive_option']) ) {
            $this->_maxSize = (int) $options['receive_option'];
        }
    }
    /**
     * init ipc message queue handler.
     * Param $queue will be encoded with crc32 to get
     * suitable queue key
     * @param mixed $queue
     */
    public function connect($queue) {
        $this->_queue = msg_get_queue(sprintf("%u", crc32($queue)));
    }
    /**
     * get element from queue
	 * @param [optional] int $messageType
     * @return mixed
     */
    public function get($messageType = false) {
        if ( empty($this->_queue) ) {
            return false;
        }
		
		if(false === $messageType)
		{
			$crcMessageType = 0;
		}
		else
		{
			$crcMessageType = sprintf("%u", crc32($messageType));
		}


        $stat = $this->getStats();
        if ( $stat['msg_qnum'] > 0 ) {
            if ( true !== msg_receive($this->_queue, $crcMessageType , $msgtype_real, $this->_maxSize, $message, $this->_serialize, $this->_receiveOpt, $err) ) {
                $this->_lastError = $err;
                return false;
            }
            return $message;
        }
        return false;
    }
    /**
     * add element to queue
     * @param mixed $message
	 * @param [optional] int $messageType
     */
    public function add($message, $messageType = false) {
        if ( empty($this->_queue) ) {
            return false;
        }

		if(false === $messageType)
		{
			$crcMessageType = $this->_msgType;
		}
		else
		{
			$crcMessageType = sprintf("%u", crc32($messageType));
		}

		if ( false === msg_send($this->_queue, $crcMessageType , $message, $this->_serialize, $this->_blockSend, $err) ) {
            $this->_lastError = $err;
            return false;
        }
        return true;
    }
    /**
     * return queue status
     *
     * msg_perm.uid  	The uid of the owner of the queue.
     *
     * msg_perm.gid 	The gid of the owner of the queue.
     *
     * msg_perm.mode 	The file access mode of the queue.
     *
     * msg_stime        The time that the last message was sent to the queue.
     *
     * msg_rtime        The time that the last message was received from the queue.
     *
     * msg_ctime        The time that the queue was last changed.
     *
     * msg_qnum         The number of messages waiting to be read from the queue.
     *
     * msg_qbytes       The maximum number of bytes allowed in one message queue.
     *                  On Linux, this value may be read and modified via /proc/sys/kernel/msgmnb.
     *
     * msg_lspid        The pid of the process that sent the last message to the queue.
     *
     * msg_lrpid        The pid of the process that received the last message from the queue.
     *
     * @return mixed
     */
    public function getStats() {
        if ( empty($this->_queue) ) {
            return false;
        }
        return msg_stat_queue($this->_queue);
    }
    /**
     * get last error
     * @return string 
     */
    public function getLastError() {
        return $this->_lastError;
    }
}
?>
