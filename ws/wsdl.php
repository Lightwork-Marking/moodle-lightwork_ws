<?php // $Id: wsdl.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $

/**
 * This file creates a WSDL file for the web service interfaced running on
 * this server with URL paths relative to the currently running server.
 *
 * When referring to this file, you must call it as:
 *
 * http://www.yourhost.com/ ... /ws/wsdl.php
 *
 * Where ... is the path to your Moodle root.  This is so that your web server
 * will process the PHP statemtents within the file, which returns a WSDL
 * file to the web services call (or your browser).
 *
 * @version $Id: wsdl.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Open Knowledge Technologies - http://www.oktech.ca/
 */


    require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');


    header('Content-Type: application/xml; charset=UTF-8');

    $lw_schema_namespace = 'http://www.massey.ac.nz/lightwork/xsd';
    $lw_webservice_namespace = 'http://www.massey.ac.nz/lightwork/ws';

    echo '<?xml version="1.0" encoding="UTF-8"?>
<definitions
  xmlns="http://schemas.xmlsoap.org/wsdl/"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:sch="'.$lw_schema_namespace.'"
  xmlns:lwws="' . $lw_webservice_namespace .'"
  targetNamespace="' . $lw_webservice_namespace. '">

  <!-- START TYPES -->
  <types>
  <xsd:schema targetNamespace="'.$lw_schema_namespace.'"
   xmlns:sch="'.$lw_schema_namespace.'">

  <xsd:element name="loginRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="username" type="xsd:string" />
      <xsd:element name="password" type="xsd:string" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>

  <xsd:element name="coursesRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />     
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="coursesResponse">
    <xsd:complexType>
      <xsd:sequence>
        <xsd:element name="courses" type="sch:courseRecords" />
        <xsd:element name="errors" type="sch:errors" />
      </xsd:sequence>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="courseParticipantsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="courseids" >
      <xsd:complexType>
        <xsd:sequence>
          <xsd:element name="courseid" type="xsd:integer" minOccurs="0" maxOccurs="unbounded" />
        </xsd:sequence>
      </xsd:complexType>
      </xsd:element>
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="allstudents" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>

  <xsd:element name="courseParticipantsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="courseParticipants" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="courseParticipant" type="sch:courseParticipantRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>

  <xsd:element name="assignmentTeamsRequest">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentids" type="sch:assignmentids"/>
      <xsd:element name="teamids" type="sch:teamIdsInput" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="allstudents" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="assignmentTeamsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="assignments" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="assignment" type="sch:assignmentTeamsRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="submissionsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentids" type="sch:assignmentids" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="allstudents" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>

  <xsd:element name="submissionsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="assignments" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="assignment" type="sch:assignmentSubmissionRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingRubricsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentids" type="sch:assignmentids" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingRubricsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="assignments" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="assignment" type="sch:assignmentMarkingRubricRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="saveMarkingRubricsRequest">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="markingRubrics" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markingRubric" type="sch:rubricRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="sesskey" type="xsd:string" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>

  <xsd:element name="saveMarkingRubricsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="markingRubricResponses" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markingRubricResponse" type="sch:rubricRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>

  <xsd:element name="feedbackSubmissionsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentid" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />     
    </xsd:all>
    </xsd:complexType>
  </xsd:element>

  <xsd:element name="feedbackSubmissionsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="feedbackSubmissions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="feedbackSubmission" type="sch:feedbackSubmissionRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>

  <xsd:element name="demographicsRequest">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="userIdInput" type="sch:userIdInput"/>
      <xsd:element name="assignmentid" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="demographicsResponse">
  <xsd:complexType>
    <xsd:sequence>
      <xsd:element name="demographics" type="sch:demographicRecords" />
      <xsd:element name="errors" type="sch:errors" />
    </xsd:sequence>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="modifiedMarkingCountRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentids" type="sch:assignmentids" />
      <xsd:element name="type" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />   
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="activityid" type="xsd:integer"/>
      <xsd:element name="type" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="allstudents" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="markings" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="marking" type="sch:markingRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingHistoryRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="type" type="xsd:integer" />
      <xsd:element name="markingKeys" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markingKey" type="sch:markingKey" minOccurs="1" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="markingHistoryResponse">
    <xsd:complexType>     
          <xsd:sequence>
              <xsd:element name="markinghistory" type="sch:markingHistoryRecord" minOccurs="0" maxOccurs="unbounded" /> 
          </xsd:sequence>          
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="submissionFilesRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentid" type="xsd:integer" />
      <xsd:element name="submissionids" type="sch:submissionids" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="submissionFilesResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="submissionfiles" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="submissionfile" type="sch:submissionFileRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="tiifiles" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="tiifile" type="sch:tiiFileRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="saveMarkingRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="markings" type="sch:markings" />
      <xsd:element name="type" type="xsd:integer" />
      <xsd:element name="allstudents" type="xsd:integer" />   
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="releaseMarkingRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="markings" type="sch:markings" />
      <xsd:element name="type" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="saveMarkingResponse">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="markingresponses" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markingresponse" type="sch:markingResponseRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="uploadAssignmentDocumentsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentfiles" type="sch:uploadAssignmentDocumentsInput" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="uploadAssignmentDocumentsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="downloadAssignmentDocumentsMetaDataRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="courseid" type="xsd:integer" />
      <xsd:element name="assignmentid" type="xsd:integer" />
      <xsd:element name="includeannotatedfiles" type="xsd:integer" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="downloadAssignmentDocumentsMetaDataResponse">
  <xsd:complexType>
      <xsd:all>
        <xsd:element name="documentmetadata">
          <xsd:complexType>
            <xsd:sequence>
              <xsd:element name="metadata" type="sch:metaDataRecords" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
        </xsd:element>
        <xsd:element name="errors" type="sch:errors" />
      </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="repairLightworkDataRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="assignmentids" type="sch:assignmentids" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="repairLightworkDataResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="assignmentdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="assignmentdeletion" type="sch:assignmentKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="userdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="userdeletion" type="sch:userKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="teamdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="teamdeletion" type="sch:teamKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="teamstudentdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="teamstudentdeletion" type="sch:teamStudentKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="participantdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="participantdeletion" type="sch:participantKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="rubricdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="rubricdeletion" type="sch:rubricKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="markingdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markingdeletion" type="sch:markingKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="markinghistorydeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="markinghistorydeletion" type="sch:markingHistoryKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="teammarkingdeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="teammarkingdeletion" type="sch:markingKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="teammarkinghistorydeletions" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="teammarkinghistorydeletion" type="sch:markingHistoryKey" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" >
          <xsd:complexType >
            <xsd:sequence>
              <xsd:element name="error" type="sch:errorRecord" minOccurs="0" maxOccurs="unbounded" />
            </xsd:sequence>
          </xsd:complexType>
      </xsd:element>
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="assignmentDocumentsRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="courseid" type="xsd:integer" />
      <xsd:element name="assignmentid" type="xsd:integer" />
      <xsd:element name="filenames" type="sch:filenames" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="assignmentDocumentsResponse">
  <xsd:complexType>
    <xsd:all>
      <xsd:element name="files">
        <xsd:complexType>
          <xsd:sequence>
            <xsd:element name="fileInfo" type="sch:fileRecord" minOccurs="0" maxOccurs="unbounded" />
          </xsd:sequence>
        </xsd:complexType>
      </xsd:element>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:all>
  </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="submissionReportRequest">
    <xsd:complexType>
    <xsd:all>
      <xsd:element name="sesskey" type="xsd:string" />
      <xsd:element name="courseid" type="xsd:integer" />
      <xsd:element name="startdate" type="xsd:date" />
      <xsd:element name="enddate" type="xsd:date" />
    </xsd:all>
    </xsd:complexType>
  </xsd:element>
  
  <xsd:element name="submissionReportResponse">
  <xsd:complexType>
    <xsd:sequence>
      <xsd:element name="studentreportrecords" type="sch:studentreportrecords"/>
      <xsd:element name="errors" type="sch:errors" />
    </xsd:sequence>
  </xsd:complexType>
  </xsd:element>
  
  <!--  END ELEMENTS -->

  <xsd:complexType name="assignmentRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="course" type="xsd:integer" />
      <xsd:element name="name" type="xsd:string" />
      <xsd:element name="timedue" type="xsd:integer" />
      <xsd:element name="assignmenttype" type="xsd:string" />
      <xsd:element name="grade" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="assignmentRecords">
    <xsd:sequence>
      <xsd:element name="assignment" type="sch:assignmentRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="courseRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="fullname" type="xsd:string" />
      <xsd:element name="shortname" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="assignments" type="sch:assignmentRecords" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="courseRecords">
    <xsd:sequence>
      <xsd:element name="course" type="sch:courseRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="courseParticipantRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="user" type="sch:userRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="userRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="username" type="xsd:string" />
      <xsd:element name="idnumber" type="xsd:string" />
      <xsd:element name="firstname" type="xsd:string" />
      <xsd:element name="lastname" type="xsd:string" />
      <xsd:element name="roleid" type="xsd:integer" />
      <xsd:element name="capabilitycode" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="teamIdsInput">
    <xsd:sequence>
      <xsd:element name="teamid" type="xsd:integer" minOccurs="1" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="assignmentTeamsRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="teams">
        <xsd:complexType>
          <xsd:sequence>
      		<xsd:element name="team" type="sch:teamRecord" minOccurs="0" maxOccurs="unbounded" />
          </xsd:sequence>
        </xsd:complexType>
      </xsd:element>
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="teamRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="name" type="xsd:string" minOccurs="1" maxOccurs="1" />
      <xsd:element name="membershipopen" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="timemodified" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="students">
        <xsd:complexType>
          <xsd:sequence>
      		<xsd:element name="student" type="sch:teamStudentRecord" minOccurs="0" maxOccurs="unbounded" />
          </xsd:sequence>
        </xsd:complexType>
      </xsd:element>
      </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="teamStudentRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="studentid" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="timemodified" type="xsd:integer" minOccurs="1" maxOccurs="1" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="userIdInput">
    <xsd:sequence>
      <xsd:element name="userid" minOccurs="1" maxOccurs="unbounded" >
          <xsd:complexType >
            <xsd:all>
              <xsd:element name="userid" type="xsd:integer" />
              <xsd:element name="timemodified" type="xsd:integer" />
            </xsd:all>
          </xsd:complexType>
      </xsd:element>
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="submissionRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="userid" type="xsd:integer" />
      <xsd:element name="timecreated" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="numfiles" type="xsd:integer" />
      <xsd:element name="status" type="xsd:string" />
      <xsd:element name="grade" type="xsd:decimal" />
      <xsd:element name="submissioncomment" type="xsd:string" />
      <xsd:element name="teacher" type="xsd:integer" />
      <xsd:element name="timemarked" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>

  <xsd:complexType name="assignmentSubmissionRecord">
    <xsd:sequence>
      <xsd:element name="assignmentid" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="submissions" type="sch:submissionRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="rubricRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer"  />
      <xsd:element name="activity" type="xsd:integer"  />
      <xsd:element name="activitytype" type="xsd:integer" />
      <xsd:element name="xmltextref" type="xsd:string" />
      <xsd:element name="complete" type="xsd:integer" />
      <xsd:element name="deleted" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer"  />
    </xsd:all>
  </xsd:complexType>

  <xsd:complexType name="assignmentMarkingRubricRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" minOccurs="1" maxOccurs="1" />
      <xsd:element name="rubric" type="sch:rubricRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="feedbackSubmissionRecord">
    <xsd:sequence>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="submission" type="xsd:integer" />
      <xsd:element name="paper" type="xsd:string" />
      <xsd:element name="duedate" type="xsd:integer" />
      <xsd:element name="topic" type="xsd:string" />
      <xsd:element name="wordlimit" type="xsd:integer" />
      <xsd:element name="referencingstyle" type="xsd:string" />
      <xsd:element name="questions" type="xsd:string" />
      <xsd:element name="difficulties" type="xsd:string" />
      <xsd:element name="timefirstsubmitted" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="demographicRecords">
    <xsd:sequence>
      <xsd:element name="demographic" type="sch:demographicRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="demographicRecord">
    <xsd:sequence>
      <xsd:element name="userid" type="xsd:integer" />
      <xsd:element name="data" type="xsd:string" />
      <xsd:element name="shortname" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="markings">
    <xsd:sequence>
      <xsd:element name="marking" type="sch:markingRecord" minOccurs="1" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="markingRecord">
    <xsd:sequence>
      <xsd:element name="marker" type="xsd:integer" />
      <xsd:element name="markable" type="xsd:integer" />
      <xsd:element name="rubric" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
      <xsd:element name="activitytype" type="xsd:integer" />
      <xsd:element name="xmltextref" type="xsd:string" />
      <xsd:element name="statuscode" type="xsd:string" />
      <xsd:element name="deleted" type="xsd:integer" />
      <xsd:element name="grade" type="xsd:decimal" minOccurs="0"/>
      <xsd:element name="submissioncomment" type="xsd:string" minOccurs="0"/>
      <xsd:element name="annotatedRecords" minOccurs="0" maxOccurs="unbounded" >
        <xsd:complexType >
          <xsd:sequence>
            <xsd:element name="fileref" type="xsd:string" minOccurs="1" maxOccurs="1"/>
            <xsd:element name="filename" type="xsd:string" minOccurs="1" maxOccurs="1"/>
            <xsd:element name="owner" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
          </xsd:sequence>
        </xsd:complexType>
      </xsd:element>
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="markinghistory" type="sch:markingHistoryRecord" minOccurs="0" maxOccurs="unbounded" />
      <xsd:element name="teammemberdeduction" type="sch:teamMemberDeductionRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="markingHistoryRecord">
    <xsd:sequence>
      <xsd:element name="lwid" type="xsd:integer" />
      <xsd:element name="statuscode" type="xsd:string" minOccurs="0"/>
      <xsd:element name="comment" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="marker" type="xsd:integer" />
      <xsd:element name="markable" type="xsd:integer" />
      <xsd:element name="rubric" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="teamMemberDeductionRecord">
    <xsd:sequence>
      <xsd:element name="member" type="xsd:integer" />
      <xsd:element name="releasecomment" type="xsd:string" />
      <xsd:element name="deductionmark" type="xsd:integer" />
      <xsd:element name="deductioncomment" type="xsd:string" />
      <xsd:element name="finalmark" type="xsd:integer" />    
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="markingResponseRecord">
    <xsd:sequence>
      <xsd:element name="marker" type="xsd:integer" />
      <xsd:element name="markable" type="xsd:integer" />
      <xsd:element name="rubric" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="markinghistoryresponse" type="sch:markingHistoryResponseRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="markingHistoryResponseRecord">
    <xsd:all>
      <xsd:element name="lwid" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="submissionFileRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="status" type="xsd:string" />
      <xsd:element name="fileref" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="tiiFileRecord">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="submissionid" type="xsd:integer" />
      <xsd:element name="filename" type="xsd:string" />
      <xsd:element name="tiiscore" type="xsd:integer" />
      <xsd:element name="tiicode" type="xsd:string" />
      <xsd:element name="tiilink" type="xsd:string" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="uploadAssignmentDocumentsInput">
    <xsd:sequence>
      <xsd:element name="assignmentfile" minOccurs="1" maxOccurs="unbounded" >
        <xsd:complexType >
          <xsd:sequence>
            <xsd:element name="assignmentid" type="xsd:integer" />
            <xsd:element name="assignmentfileresponse" minOccurs="1" maxOccurs="unbounded" >
              <xsd:complexType >
                <xsd:all>
                  <xsd:element name="fileref" type="xsd:string" />
                  <xsd:element name="filename" type="xsd:string" />
                </xsd:all>
              </xsd:complexType>
            </xsd:element>
          </xsd:sequence>
        </xsd:complexType>
      </xsd:element>
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="metaDataRecords">
    <xsd:all>      
      <xsd:element name="metadatainformation" type="xsd:string" />
      <xsd:element name="modificationtime" type="xsd:string" />      
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="fileRecord">
    <xsd:all>
      <xsd:element name="filename" type="xsd:string" />
      <xsd:element name="filesize" type="xsd:integer" />
      <xsd:element name="timemodified" type="xsd:integer" />
      <xsd:element name="fileref" type="xsd:string" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="studentreportrecords">
    <xsd:sequence>
      <xsd:element name="studentreportrecord" type="sch:studentReportRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="studentReportRecord">
    <xsd:sequence>
      <xsd:element name="assignmentid" type="xsd:integer" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="assignmentname" type="xsd:string" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="userid" type="xsd:integer" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="studentid" type="xsd:string" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="firstname" type="xsd:string" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="lastname" type="xsd:string" minOccurs="1" maxOccurs="1"/>
      <xsd:element name="paper" type="xsd:string" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="duedate" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="wordlimit" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="referencingstyle" type="xsd:string" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="submissiondate" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="markerid" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="markerfirstname" type="xsd:string" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="markerlastname" type="xsd:string" minOccurs="0" maxOccurs="1"/>
      <xsd:element name="status" type="xsd:string" minOccurs="0" maxOccurs="1"/>     
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="assignmentKey">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="userKey">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="teamKey">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="teamStudentKey">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
   
  <xsd:complexType name="participantKey">
    <xsd:all>
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="courseid" type="xsd:integer" />
      <xsd:element name="username" type="xsd:string" />
      <xsd:element name="idnumber" type="xsd:string" />
      <xsd:element name="firstname" type="xsd:string" />
      <xsd:element name="lastname" type="xsd:string" />
      <xsd:element name="timemodified" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="rubricKey">
    <xsd:all>
      <xsd:element name="lwid" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="markingKey">
    <xsd:all>
      <xsd:element name="markable" type="xsd:integer" />
      <xsd:element name="marker" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
      <xsd:element name="rubric" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="markingHistoryKey">
    <xsd:all>
      <xsd:element name="lwid" type="xsd:integer" />
      <xsd:element name="markable" type="xsd:integer" />
      <xsd:element name="marker" type="xsd:integer" />
      <xsd:element name="activity" type="xsd:integer" />
      <xsd:element name="rubric" type="xsd:integer" />
    </xsd:all>
  </xsd:complexType>
  
  <xsd:complexType name="courseids">
    <xsd:sequence>
      <xsd:element name="courseid" type="xsd:integer" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="assignmentids">
    <xsd:sequence>
      <xsd:element name="assignmentid" type="xsd:integer" minOccurs="1" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="submissionids">
    <xsd:sequence>
      <xsd:element name="submissionid" type="xsd:integer" minOccurs="1" maxOccurs="unbounded"/>
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:complexType name="filenames">
    <xsd:sequence>
      <xsd:element name="filename" type="xsd:string" minOccurs="1" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>

  <xsd:complexType name="errorRecord">
    <xsd:all>
      <xsd:element name="element" type="xsd:string" />
      <xsd:element name="id" type="xsd:integer" />
      <xsd:element name="errorcode" type="xsd:string" />
      <xsd:element name="errormessage" type="xsd:string" />
    </xsd:all>
  </xsd:complexType>

  <xsd:complexType name="errors">
    <xsd:sequence>
      <xsd:element name="error" type="sch:errorRecord" minOccurs="0" maxOccurs="unbounded" />
    </xsd:sequence>
  </xsd:complexType>
  
  <xsd:element name="sessionKey" type="xsd:string"/>
  <xsd:element name="publickey" type="xsd:string"/>
  <xsd:element name="version" type="xsd:string"/>
  <xsd:element name="trueOrFalse" type="xsd:boolean"/>
  <xsd:element name="count" type="xsd:integer"/>
    

  </xsd:schema>
  </types>
  <!--  END TYPES -->
  

  <!-- START MESSAGES -->

  <message name="mdl_soapserver.loginRequest">
    <part name="loginRequest" element="sch:loginRequest"/>
  </message>

  <message name="mdl_soapserver.loginResponse">
    <part name="sessionKey" element="sch:sessionKey" />
  </message>

  <message name="mdl_soapserver.logoutRequest">
    <part name="sessionKey" element="sch:sessionKey" />
  </message>

  <message name="mdl_soapserver.logoutResponse">
    <part name="return" element="sch:trueOrFalse" />
  </message>

  <message name="mdl_soapserver.getPublicKeyResponse">
    <part name="publickey" element="sch:publickey"/>
  </message>

  <message name="mdl_soapserver.getServiceVersionResponse">
    <part name="version" element="sch:version"/>
  </message>

  <message name="mdl_soapserver.getCoursesRequest">
    <part name="coursesRequest" element="sch:coursesRequest"/>
  </message>
  
  <message name="mdl_soapserver.getCoursesResponse">
    <part name="coursesResponse" element="sch:coursesResponse" />
  </message>

  <message name="mdl_soapserver.getCourseParticipantsRequest">
    <part name="courseParticipantsRequest" element="sch:courseParticipantsRequest" />
  </message>

  <message name="mdl_soapserver.getCourseParticipantsResponse">
    <part name="courseParticipantsResponse" element="sch:courseParticipantsResponse" />
  </message>

  <message name="mdl_soapserver.getAssignmentTeamsRequest">
    <part name="assignmentTeamsRequest" element="sch:assignmentTeamsRequest" />
  </message>
    
  <message name="mdl_soapserver.getAssignmentTeamsResponse">
  	<part name="assignmentTeamsResponse" element="sch:assignmentTeamsResponse" />
  </message>
  
  <message name="mdl_soapserver.getSubmissionsRequest">
    <part name="submissionsRequest" element="sch:submissionsRequest" />
  </message>

  <message name="mdl_soapserver.getSubmissionsResponse">
    <part name="submissionsResponse" element="sch:submissionsResponse" />
  </message>
  
  <message name="mdl_soapserver.getMarkingRubricsRequest">
    <part name="markingRubricsRequest" element="sch:markingRubricsRequest" />
  </message>
  
  <message name="mdl_soapserver.getMarkingRubricsResponse">
    <part name="markingRubricsResponse" element="sch:markingRubricsResponse" />
  </message>
  
  <message name="mdl_soapserver.saveMarkingRubricsRequest">
    <part name="saveMarkingRubricsRequest" element="sch:saveMarkingRubricsRequest" />
  </message>

  <message name="mdl_soapserver.saveMarkingRubricsResponse">
    <part name="saveMarkingRubricsResponse" element="sch:saveMarkingRubricsResponse" />
  </message>
  
  <message name="mdl_soapserver.getFeedbackSubmissionsRequest">
    <part name="feedbackSubmissionsRequest" element="sch:feedbackSubmissionsRequest" />
  </message>
  
  <message name="mdl_soapserver.getFeedbackSubmissionsResponse">
    <part name="feedbackSubmissionsResponse" element="sch:feedbackSubmissionsResponse" />
  </message>
  
  <message name="mdl_soapserver.getDemographicsRequest">
    <part name="demographicsRequest" element="sch:demographicsRequest" />
  </message>
  
  <message name="mdl_soapserver.getDemographicsResponse">
    <part name="demographicsResponse" element="sch:demographicsResponse" />
  </message>
  
  <message name="mdl_soapserver.getModifiedMarkingCountRequest">
    <part name="modifiedMarkingCountRequest" element="sch:modifiedMarkingCountRequest" />
  </message>
  
  <message name="mdl_soapserver.getModifiedMarkingCountResponse">
    <part name="count" element="sch:count" />
  </message>

  <message name="mdl_soapserver.getMarkingRequest">
    <part name="markingRequest" element="sch:markingRequest" />
  </message>
  
  <message name="mdl_soapserver.getMarkingResponse">
    <part name="markingResponse" element="sch:markingResponse" />
  </message>

  <message name="mdl_soapserver.getMarkingHistoryRequest">
    <part name="markingHistoryRequest" element="sch:markingHistoryRequest" />
  </message>
  
  <message name="mdl_soapserver.getMarkingHistoryResponse">
    <part name="markingHistoryResponse" element="sch:markingHistoryResponse" />
  </message>

  <message name="mdl_soapserver.getSubmissionFilesRequest">
    <part name="submissionFilesRequest" element="sch:submissionFilesRequest" />
  </message>

  <message name="mdl_soapserver.getSubmissionFilesResponse">
    <part name="submissionFilesResponse" element="sch:submissionFilesResponse" />
  </message>

  <message name="mdl_soapserver.saveMarkingRequest">
    <part name="saveMarkingRequest" element="sch:saveMarkingRequest" />
  </message>
  
  <message name="mdl_soapserver.saveMarkingResponse">
    <part name="saveMarkingResponse" element="sch:saveMarkingResponse" />
  </message>
  
  <message name="mdl_soapserver.releaseMarkingRequest">
    <part name="releaseMarkingRequest" element="sch:releaseMarkingRequest" />
  </message>

  <message name="mdl_soapserver.uploadAssignmentDocumentsRequest">
    <part name="uploadAssignmentDocumentsRequest" element="sch:uploadAssignmentDocumentsRequest" />
  </message>

  <message name="mdl_soapserver.uploadAssignmentDocumentsResponse">
    <part name="uploadAssignmentDocumentsResponse" element="sch:uploadAssignmentDocumentsResponse" />
  </message>
  
  <message name="mdl_soapserver.downloadAssignmentDocumentsMetaDataRequest">
    <part name="downloadAssignmentDocumentsMetaDataRequest" element="sch:downloadAssignmentDocumentsMetaDataRequest" />
  </message>
    
  <message name="mdl_soapserver.downloadAssignmentDocumentsMetaDataResponse">
    <part name="downloadAssignmentDocumentsMetaDataResponse" element="sch:downloadAssignmentDocumentsMetaDataResponse" />
  </message>
  
  <message name="mdl_soapserver.repairLightworkDataRequest">
    <part name="repairLightworkDataRequest" element="sch:repairLightworkDataRequest" />
  </message>
  
  <message name="mdl_soapserver.repairLightworkDataResponse">
    <part name="repairLightworkDataResponse" element="sch:repairLightworkDataResponse" />
  </message>

  <message name="mdl_soapserver.getAssignmentDocumentsRequest">
    <part name="assignmentDocumentsRequest" element="sch:assignmentDocumentsRequest" />
  </message>

  <message name="mdl_soapserver.getAssignmentDocumentsResponse">
    <part name="assignmentDocumentsResponse" element="sch:assignmentDocumentsResponse" />
  </message>
    
  <message name="mdl_soapserver.getSubmissionReportRequest">
    <part name="submissionReportRequest" element="sch:submissionReportRequest" />
  </message>
  
  <message name="mdl_soapserver.getSubmissionReportResponse">
    <part name="submissionReportResponse" element="sch:submissionReportResponse" />
  </message>

  <message name="empty">
  </message>
  
  
  
  
  <!-- END MESSAGES -->

  <!-- START PORTS -->

  <portType name="MoodleWSPortType">

    <operation name="mdl_soapserver.login">
      <input message="lwws:mdl_soapserver.loginRequest" />
      <output message="lwws:mdl_soapserver.loginResponse" />
    </operation>

    <operation name="mdl_soapserver.logout">
      <input message="lwws:mdl_soapserver.logoutRequest" />
      <output message="lwws:mdl_soapserver.logoutResponse" />
    </operation>

    <operation name="mdl_soapserver.getPublicKey">
      <input message="lwws:empty" />
      <output message="lwws:mdl_soapserver.getPublicKeyResponse" />
    </operation>

    <operation name="mdl_soapserver.getServiceVersion">
      <input message="lwws:empty" />
      <output message="lwws:mdl_soapserver.getServiceVersionResponse" />
    </operation>

    <operation name="mdl_soapserver.getCourses">
      <input message="lwws:mdl_soapserver.getCoursesRequest" />
      <output message="lwws:mdl_soapserver.getCoursesResponse" />
    </operation>

    <operation name="mdl_soapserver.getCourseParticipants">
      <input message="lwws:mdl_soapserver.getCourseParticipantsRequest" />
      <output message="lwws:mdl_soapserver.getCourseParticipantsResponse" />
    </operation>

    <operation name="mdl_soapserver.getAssignmentTeams">
      <input message="lwws:mdl_soapserver.getAssignmentTeamsRequest" />
      <output message="lwws:mdl_soapserver.getAssignmentTeamsResponse" />
    </operation>
    
    <operation name="mdl_soapserver.getSubmissions">
      <input message="lwws:mdl_soapserver.getSubmissionsRequest" />
      <output message="lwws:mdl_soapserver.getSubmissionsResponse" />
    </operation>

    <operation name="mdl_soapserver.getMarkingRubrics">
      <input message="lwws:mdl_soapserver.getMarkingRubricsRequest" />
      <output message="lwws:mdl_soapserver.getMarkingRubricsResponse" />
    </operation>

    <operation name="mdl_soapserver.saveMarkingRubrics">
      <input message="lwws:mdl_soapserver.saveMarkingRubricsRequest" />
      <output message="lwws:mdl_soapserver.saveMarkingRubricsResponse" />
    </operation>
    
    <operation name="mdl_soapserver.getFeedbackSubmissions">
      <input message="lwws:mdl_soapserver.getFeedbackSubmissionsRequest" />
      <output message="lwws:mdl_soapserver.getFeedbackSubmissionsResponse" />
    </operation>
    
    <operation name="mdl_soapserver.getDemographics">
      <input message="lwws:mdl_soapserver.getDemographicsRequest" />
      <output message="lwws:mdl_soapserver.getDemographicsResponse" />
    </operation> 
    
    <operation name="mdl_soapserver.getModifiedMarkingCount">
      <input message="lwws:mdl_soapserver.getModifiedMarkingCountRequest" />
      <output message="lwws:mdl_soapserver.getModifiedMarkingCountResponse" />
    </operation>

    <operation name="mdl_soapserver.getMarking">
      <input message="lwws:mdl_soapserver.getMarkingRequest" />
      <output message="lwws:mdl_soapserver.getMarkingResponse" />
    </operation>

    <operation name="mdl_soapserver.getMarkingHistory">
      <input message="lwws:mdl_soapserver.getMarkingHistoryRequest" />
      <output message="lwws:mdl_soapserver.getMarkingHistoryResponse" />
    </operation>

    <operation name="mdl_soapserver.getSubmissionFiles">
      <input message="lwws:mdl_soapserver.getSubmissionFilesRequest" />
      <output message="lwws:mdl_soapserver.getSubmissionFilesResponse" />
    </operation>

    <operation name="mdl_soapserver.saveMarking">
      <input message="lwws:mdl_soapserver.saveMarkingRequest" />
      <output message="lwws:mdl_soapserver.saveMarkingResponse" />
    </operation>
    
    <operation name="mdl_soapserver.releaseMarking">
      <input message="lwws:mdl_soapserver.releaseMarkingRequest" />
      <output message="lwws:mdl_soapserver.saveMarkingResponse" />
    </operation>
    
    <operation name="mdl_soapserver.releaseTeamMarking">
      <input message="lwws:mdl_soapserver.releaseMarkingRequest" />
      <output message="lwws:mdl_soapserver.saveMarkingResponse" />
    </operation>

    <operation name="mdl_soapserver.uploadAssignmentDocuments">
      <input message="lwws:mdl_soapserver.uploadAssignmentDocumentsRequest" />
      <output message="lwws:mdl_soapserver.uploadAssignmentDocumentsResponse" />
    </operation>
    
    <operation name="mdl_soapserver.downloadAssignmentDocumentsMetaData">
      <input message="lwws:mdl_soapserver.downloadAssignmentDocumentsMetaDataRequest" />
      <output message="lwws:mdl_soapserver.downloadAssignmentDocumentsMetaDataResponse" />
    </operation>
    
    <operation name="mdl_soapserver.repairLightworkData">
      <input message="lwws:mdl_soapserver.repairLightworkDataRequest" />
      <output message="lwws:mdl_soapserver.repairLightworkDataResponse" />
    </operation>

    <operation name="mdl_soapserver.getAssignmentDocuments">
      <input message="lwws:mdl_soapserver.getAssignmentDocumentsRequest" />
      <output message="lwws:mdl_soapserver.getAssignmentDocumentsResponse" />
    </operation>
    
    <operation name="mdl_soapserver.getSubmissionReport">
      <input message="lwws:mdl_soapserver.getSubmissionReportRequest" />
      <output message="lwws:mdl_soapserver.getSubmissionReportResponse" />
    </operation>

</portType>

  <!-- END PORTS -->


  <!-- START BINDINGS -->

  <binding name="MoodleWSBinding" type="lwws:MoodleWSPortType">
    <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http" />

    <operation name="mdl_soapserver.login">
      <soap:operation
        soapAction="http://www.massey.ac.nz/lightwork/ws#login"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.logout">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#logout"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getPublicKey">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getPublicKey"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getServiceVersion">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getServiceVersion"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getCourses">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getCourses"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getCourseParticipants">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getCourseParticipants"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getAssignmentTeams">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getAssignmentTeams"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.getSubmissions">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getSubmissions"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.getMarkingRubrics">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getMarkingRubrics"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.saveMarkingRubrics">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#saveMarkingRubrics"
        style="document" />
      <input>
        <soap:body use="literal" />
      </input>
      <output>
        <soap:body use="literal" />
      </output>
    </operation>    

    <operation name="mdl_soapserver.getFeedbackSubmissions">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getFeedbackSubmissions"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.getDemographics">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getDemographics"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>    
    
    <operation name="mdl_soapserver.getModifiedMarkingCount">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getModifiedMarkingCount"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getMarking">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getMarking"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.getMarkingHistory">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getMarkingHistory"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getSubmissionFiles">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getSubmissionFiles"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.saveMarking">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#saveMarking"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.releaseMarking">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#releaseMarking"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
   <operation name="mdl_soapserver.releaseTeamMarking">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#releaseTeamMarking"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.uploadAssignmentDocuments">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#uploadAssignmentDocuments"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.downloadAssignmentDocumentsMetaData">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#downloadAssignmentDocumentsMetaData"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.repairLightworkData">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#repairLightworkData"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    <operation name="mdl_soapserver.getAssignmentDocuments">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getAssignmentDocuments"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>
    
    <operation name="mdl_soapserver.getSubmissionReport">
      <soap:operation
        soapAction="'.$lw_webservice_namespace.'#getSubmissionReport"
        style="document" />
      <input>
        <soap:body use="literal"/>
      </input>
      <output>
        <soap:body use="literal"/>
      </output>
    </operation>

    </binding>

  <!-- END BINDINGS -->

  <service name="MoodleWS">
    <port name="MoodleWSPort" binding="lwws:MoodleWSBinding">
      <soap:address
        location="' . $CFG->wwwroot . '/local/lightwork/ws/service.php?type=soap" />
    </port>
  </service>
</definitions>';

?>